#!/bin/bash
# Script to find your local IP address

echo "Finding your local IP address..."
echo ""

# Try different methods
if command -v ip &> /dev/null; then
    echo "Method 1 (ip command):"
    ip route get 8.8.8.8 2>/dev/null | awk '{print $7}' | head -1
    echo ""
    echo "Method 2 (ip addr):"
    ip addr show | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | cut -d/ -f1 | head -1
elif command -v ifconfig &> /dev/null; then
    echo "Method (ifconfig):"
    ifconfig | grep "inet " | grep -v 127.0.0.1 | awk '{print $2}' | head -1
else
    echo "Could not find IP. Please check your network settings manually."
fi

echo ""
echo "Common IP ranges:"
echo "  - 192.168.x.x (home networks)"
echo "  - 10.0.x.x (some networks)"
echo "  - 172.16.x.x to 172.31.x.x (some networks)"













