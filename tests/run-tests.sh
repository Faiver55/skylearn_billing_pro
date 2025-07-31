#!/bin/bash

# Skylearn Billing Pro Test Runner
# This script runs the test suite for the plugin

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

echo -e "${GREEN}Skylearn Billing Pro Test Runner${NC}"
echo "========================================"

# Check if PHPUnit is available
if ! command -v phpunit &> /dev/null; then
    echo -e "${RED}PHPUnit is not installed or not in PATH${NC}"
    echo "Please install PHPUnit to run tests"
    exit 1
fi

# Check if PHPUnit config exists
if [ ! -f "$PLUGIN_DIR/phpunit.xml" ]; then
    echo -e "${RED}PHPUnit configuration file not found${NC}"
    echo "Expected: $PLUGIN_DIR/phpunit.xml"
    exit 1
fi

# Change to plugin directory
cd "$PLUGIN_DIR"

# Parse command line arguments
RUN_UNIT=true
RUN_INTEGRATION=false
COVERAGE=false
VERBOSE=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --integration)
            RUN_INTEGRATION=true
            shift
            ;;
        --coverage)
            COVERAGE=true
            shift
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        --help)
            echo "Usage: $0 [options]"
            echo ""
            echo "Options:"
            echo "  --integration    Run integration tests"
            echo "  --coverage       Generate coverage report"
            echo "  --verbose        Show verbose output"
            echo "  --help          Show this help message"
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

# Build PHPUnit command
PHPUNIT_CMD="phpunit"

if [ "$COVERAGE" = true ]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --coverage-html tests/coverage --coverage-text"
fi

if [ "$VERBOSE" = true ]; then
    PHPUNIT_CMD="$PHPUNIT_CMD --verbose"
fi

# Run unit tests
if [ "$RUN_UNIT" = true ]; then
    echo -e "${YELLOW}Running unit tests...${NC}"
    $PHPUNIT_CMD tests/unit/
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Unit tests passed${NC}"
    else
        echo -e "${RED}✗ Unit tests failed${NC}"
        exit 1
    fi
fi

# Run integration tests
if [ "$RUN_INTEGRATION" = true ]; then
    echo -e "${YELLOW}Running integration tests...${NC}"
    $PHPUNIT_CMD tests/integration/
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Integration tests passed${NC}"
    else
        echo -e "${RED}✗ Integration tests failed${NC}"
        exit 1
    fi
fi

# Display coverage report location
if [ "$COVERAGE" = true ]; then
    echo ""
    echo -e "${GREEN}Coverage report generated:${NC}"
    echo "HTML: tests/coverage/index.html"
    echo "Text: tests/coverage.txt"
fi

echo ""
echo -e "${GREEN}All tests completed successfully!${NC}"