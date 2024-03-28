#!/bin/bash

echo "Running update_plugin_version.sh..."

# Get the latest version (assuming it's stored as a Git tag)
latest_version=$(git describe --tags $(git rev-list --tags --max-count=1))
echo "Latest version determined: $latest_version"

# Check if latest_version is not empty
if [ -z "$latest_version" ]; then
    echo "Error: Could not determine latest version."
    exit 1
fi

# Attempt to update the plugin.php file
echo "Attempting to update plugin.php with latest version..."
sed -i "s/\$version = '[^']*';/\$version = '$latest_version';/" plugin.php

# Check if plugin.php was updated
if ! git diff --exit-code --quiet plugin.php; then
    echo "plugin.php was updated. Proceeding with commit..."

    # Configure Git (these details will appear in the commit log)
    git config --global user.email "fadi@asbih.com"
    git config --global user.name "Fadi Asbih"

    # Add and commit changes
    git add plugin.php
    git commit -m "chore: update version in plugin.php to $latest_version"
else
    echo "No changes to plugin.php detected."
fi
