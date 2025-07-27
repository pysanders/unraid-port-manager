#!/bin/bash

# Build script for Port Manager Plugin
# Creates a Slackware-compatible package for Unraid

PLUGIN_NAME="port-manager"
VERSION="2025.07.27s"
ARCH="x86_64"
BUILD="1"

echo "Building ${PLUGIN_NAME} plugin..."

# Create package directory structure
mkdir -p package/usr/local/emhttp/plugins/${PLUGIN_NAME}

# Copy plugin files
cp -r source/usr/local/emhttp/plugins/${PLUGIN_NAME}/* package/usr/local/emhttp/plugins/${PLUGIN_NAME}/

# Create package info
mkdir -p package/install
cat > package/install/slack-desc << EOF
${PLUGIN_NAME}: ${PLUGIN_NAME} (Port Manager for Unraid)
${PLUGIN_NAME}:
${PLUGIN_NAME}: A comprehensive port management plugin that displays all ports
${PLUGIN_NAME}: in use across your Unraid system including Docker containers,
${PLUGIN_NAME}: VMs, and system services. Features search functionality and
${PLUGIN_NAME}: port suggestion capabilities.
${PLUGIN_NAME}:
${PLUGIN_NAME}: Homepage: https://github.com/nathansanders/unraid-port-manager
${PLUGIN_NAME}: 
${PLUGIN_NAME}:
${PLUGIN_NAME}:
EOF

# Create the package using xz compression (Slackware format)
cd package
tar --create --xz --file ../${PLUGIN_NAME}-${VERSION}-${ARCH}-${BUILD}.txz *
cd ..

# Generate MD5 checksum
md5sum ${PLUGIN_NAME}-${VERSION}-${ARCH}-${BUILD}.txz > ${PLUGIN_NAME}-${VERSION}-${ARCH}-${BUILD}.txz.md5

# Cleanup
rm -rf package

echo "Package created: ${PLUGIN_NAME}-${VERSION}-${ARCH}-${BUILD}.txz"
echo "MD5 checksum: $(cat ${PLUGIN_NAME}-${VERSION}-${ARCH}-${BUILD}.txz.md5)"
echo "Build complete!"