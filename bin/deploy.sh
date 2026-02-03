#!/usr/bin/env bash
##
# WPInsight Deployment Script
#
# This script creates a distributable ZIP file of the plugin, ready for
# WordPress.org submission or manual distribution.
#
# Features:
# - Reads version from plugin header automatically
# - Creates clean copy excluding development files
# - Generates ZIP with version in filename
# - Validates files before packaging
# - Saves to parent directory
#
# Usage:
#   ./bin/deploy.sh
#
# Output:
#   ../cloudfest-wporgdownload.{version}.zip
#
# @package    CloudFest_WPOrgDownload
# @author     CloudFest Team
# @license    GPL-3.0-or-later
##

set -e  # Exit on error
set -u  # Exit on undefined variable

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Script configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
PLUGIN_SLUG="cloudfest-wporgdownload"
PLUGIN_FILE="$PLUGIN_DIR/$PLUGIN_SLUG.php"
BUILD_DIR="/tmp/$PLUGIN_SLUG-build-$$"
PARENT_DIR="$(dirname "$PLUGIN_DIR")"

# Functions
print_header() {
    echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
    echo -e "${BLUE}  WPInsight Deployment Script${NC}"
    echo -e "${BLUE}═══════════════════════════════════════════════════${NC}"
}

print_step() {
    echo -e "${GREEN}▶${NC} $1"
}

print_error() {
    echo -e "${RED}✗ ERROR:${NC} $1" >&2
}

print_warning() {
    echo -e "${YELLOW}⚠ WARNING:${NC} $1"
}

print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

# Validation
validate_plugin_file() {
    if [ ! -f "$PLUGIN_FILE" ]; then
        print_error "Plugin file not found: $PLUGIN_FILE"
        exit 1
    fi
}

# Extract version from plugin header
get_plugin_version() {
    local version
    version=$(grep "^ \* Version:" "$PLUGIN_FILE" | awk '{print $3}' | tr -d '\r')

    if [ -z "$version" ]; then
        print_error "Could not extract version from plugin header"
        exit 1
    fi

    echo "$version"
}

# Check if version is valid semver
validate_version() {
    local version=$1
    if ! [[ $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
        print_warning "Version '$version' does not follow semantic versioning (x.y.z)"
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Create clean build directory
prepare_build_directory() {
    print_step "Creating build directory..."

    if [ -d "$BUILD_DIR" ]; then
        rm -rf "$BUILD_DIR"
    fi

    mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"
    print_success "Build directory created: $BUILD_DIR"
}

# Copy files excluding dev artifacts
copy_plugin_files() {
    print_step "Copying plugin files..."

    # Use rsync to copy files with exclusions
    rsync -a \
        --exclude='.git/' \
        --exclude='.gitignore' \
        --exclude='.gitattributes' \
        --exclude='node_modules/' \
        --exclude='vendor/' \
        --exclude='tests/' \
        --exclude='bin/' \
        --exclude='docs/' \
        --exclude='.phpcs.xml' \
        --exclude='.phpcs.xml.dist' \
        --exclude='phpcs.xml' \
        --exclude='phpcs.xml.dist' \
        --exclude='phpunit.xml' \
        --exclude='phpunit.xml.dist' \
        --exclude='composer.json' \
        --exclude='composer.lock' \
        --exclude='package.json' \
        --exclude='package-lock.json' \
        --exclude='webpack.config.js' \
        --exclude='.phpstan.neon' \
        --exclude='.phpstan.neon.dist' \
        --exclude='psalm.xml' \
        --exclude='.env' \
        --exclude='.env.example' \
        --exclude='*.log' \
        --exclude='*.tmp' \
        --exclude='.DS_Store' \
        --exclude='Thumbs.db' \
        --exclude='DOCUMENTATION-*.md' \
        --exclude='SECURITY-*.md' \
        --exclude='FINAL-*.md' \
        --exclude='REST-API-*.md' \
        --exclude='SYNC-*.md' \
        --exclude='TRANSLATION-*.md' \
        --exclude='*.backup' \
        --exclude='.editorconfig' \
        --exclude='.vscode/' \
        --exclude='.idea/' \
        "$PLUGIN_DIR/" "$BUILD_DIR/$PLUGIN_SLUG/"

    print_success "Files copied to build directory"
}

# Validate required files exist
validate_required_files() {
    print_step "Validating required files..."

    local required_files=(
        "$BUILD_DIR/$PLUGIN_SLUG/$PLUGIN_SLUG.php"
        "$BUILD_DIR/$PLUGIN_SLUG/readme.txt"
        "$BUILD_DIR/$PLUGIN_SLUG/uninstall.php"
        "$BUILD_DIR/$PLUGIN_SLUG/CHANGELOG.md"
    )

    for file in "${required_files[@]}"; do
        if [ ! -f "$file" ]; then
            print_error "Required file missing: $(basename "$file")"
            exit 1
        fi
    done

    print_success "All required files present"
}

# Check for common issues
check_for_issues() {
    print_step "Checking for common issues..."

    # Check for PHP short tags
    if grep -r "<?" "$BUILD_DIR/$PLUGIN_SLUG" --include="*.php" | grep -v "<?php"; then
        print_warning "PHP short tags found (may cause issues)"
    fi

    # Check for var_dump/print_r (debug code)
    if grep -r "var_dump\|print_r" "$BUILD_DIR/$PLUGIN_SLUG" --include="*.php"; then
        print_warning "Debug functions found (var_dump/print_r)"
    fi

    # Check for TODO/FIXME comments
    local todo_count
    todo_count=$(grep -r "TODO\|FIXME" "$BUILD_DIR/$PLUGIN_SLUG" --include="*.php" | wc -l)
    if [ "$todo_count" -gt 0 ]; then
        print_warning "Found $todo_count TODO/FIXME comments"
    fi

    print_success "Issue check completed"
}

# Create ZIP archive
create_zip_archive() {
    local version=$1
    local zip_name="$PLUGIN_SLUG.$version.zip"
    local zip_path="$PARENT_DIR/$zip_name"

    print_step "Creating ZIP archive..."

    # Remove existing ZIP if present
    if [ -f "$zip_path" ]; then
        print_warning "Removing existing ZIP: $zip_name"
        rm "$zip_path"
    fi

    # Create ZIP (with proper directory structure)
    cd "$BUILD_DIR"
    zip -r "$zip_path" "$PLUGIN_SLUG" -q

    if [ ! -f "$zip_path" ]; then
        print_error "Failed to create ZIP archive"
        exit 1
    fi

    # Get ZIP size
    local zip_size
    zip_size=$(du -h "$zip_path" | cut -f1)

    print_success "ZIP created: $zip_name ($zip_size)"
    echo -e "   Location: ${BLUE}$zip_path${NC}"
}

# Cleanup temporary files
cleanup() {
    if [ -d "$BUILD_DIR" ]; then
        print_step "Cleaning up temporary files..."
        rm -rf "$BUILD_DIR"
        print_success "Cleanup completed"
    fi
}

# Main execution
main() {
    print_header

    # Validate environment
    validate_plugin_file

    # Get version
    print_step "Reading plugin version..."
    VERSION=$(get_plugin_version)
    print_success "Version: $VERSION"

    # Validate version
    validate_version "$VERSION"

    # Prepare build
    prepare_build_directory

    # Copy files
    copy_plugin_files

    # Validate build
    validate_required_files
    check_for_issues

    # Create ZIP
    create_zip_archive "$VERSION"

    # Cleanup
    cleanup

    # Success summary
    echo ""
    echo -e "${GREEN}═══════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}  Deployment Complete!${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════${NC}"
    echo ""
    echo -e "Package: ${BLUE}$PLUGIN_SLUG.$VERSION.zip${NC}"
    echo -e "Location: ${BLUE}$PARENT_DIR${NC}"
    echo ""
    echo -e "Next steps:"
    echo -e "  1. Test the ZIP by installing it on a clean WordPress site"
    echo -e "  2. Verify all functionality works as expected"
    echo -e "  3. Submit to WordPress.org or distribute manually"
    echo ""
}

# Trap to ensure cleanup on exit
trap cleanup EXIT

# Run main
main "$@"
