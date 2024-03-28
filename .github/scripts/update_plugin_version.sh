#!/bin/bash

echo "Running update_plugin_version.sh..."

# Get the latest version (assuming it's stored as a Git tag)
latest_version=$(git describe --tags $(git rev-list --tags --max-count=1))
echo "Latest version determined: $latest_version"

# Remove the 'v' prefix from the version string
formatted_version=${latest_version#v}
echo "Formatted version for update: $formatted_version"

# Update the plugin.php file
echo "Attempting to update plugin.php with latest version..."
sed -i "s/\$version = '[^']*';/\$version = '$formatted_version';/" plugin.php

# Check if plugin.php was updated
if ! git diff --exit-code --quiet plugin.php; then
    echo "plugin.php was updated. Proceeding with commit..."

    # Configure Git (these details will appear in the commit log)
    git config --global user.email "you@example.com"
    git config --global user.name "Your Name"

    # Add and commit changes
    git add plugin.php
    git commit -m "chore: update version in plugin.php to $formatted_version"
else
    echo "No changes to plugin.php detected."
fi
