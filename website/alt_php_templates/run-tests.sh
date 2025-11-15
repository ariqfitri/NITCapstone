#!/bin/bash

# Minimal KidsSmart PHP Testing Script
# Runs only the essential tests

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_color() {
    echo -e "${1}${2}${NC}"
}

print_color $BLUE "=== KidsSmart Testing Suite ==="

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    print_color $YELLOW "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
    if [ $? -ne 0 ]; then
        print_color $RED "Failed to install dependencies"
        exit 1
    fi
fi

# Create results directory
mkdir -p tests/results

# Parse command line arguments
case "${1:-all}" in
    "unit")
        print_color $YELLOW "Running Unit Tests..."
        vendor/bin/phpunit tests/Unit/ProgramTest.php tests/Unit/UserTest.php --colors=always
        ;;
    
    "integration")
        print_color $YELLOW "Running Integration Tests..."
        vendor/bin/phpunit tests/Integration/IntegrationTest.php --colors=always
        ;;
    
    "all"|*)
        print_color $YELLOW "Running All Tests..."
        
        # Run unit tests
        print_color $BLUE "1. Unit Tests (Program & User models)..."
        vendor/bin/phpunit tests/Unit/ProgramTest.php tests/Unit/UserTest.php --colors=always
        unit_result=$?
        
        # Run integration tests
        print_color $BLUE "2. Integration Tests (Cross-database operations)..."
        vendor/bin/phpunit tests/Integration/IntegrationTest.php --colors=always
        integration_result=$?
        
        # Summary
        echo ""
        print_color $BLUE "=== Test Summary ==="
        
        if [ $unit_result -eq 0 ]; then
            print_color $GREEN " Unit Tests: PASSED"
        else
            print_color $RED " Unit Tests: FAILED"
        fi
        
        if [ $integration_result -eq 0 ]; then
            print_color $GREEN " Integration Tests: PASSED"
        else
            print_color $RED " Integration Tests: FAILED"
        fi
        
        # Overall result
        if [ $unit_result -eq 0 ] && [ $integration_result -eq 0 ]; then
            print_color $GREEN "All minimal tests passed!"
        else
            print_color $RED "Some tests failed"
            exit 1
        fi
        ;;
esac

print_color $BLUE "Testing completed."
echo ""
print_color $BLUE "Available commands:"
echo "  ./run-tests.sh unit         - Run unit tests only"
echo "  ./run-tests.sh integration  - Run integration tests only"
echo "  ./run-tests.sh all          - Run all tests (default)"