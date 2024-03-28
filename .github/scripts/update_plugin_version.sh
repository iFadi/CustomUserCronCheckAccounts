#!/bin/bash

# Get the latest version (assuming it's stored as a Git tag)
latest_version=$(git describe --tags `git rev-list --tags --max-count=1`)

# Check if latest_version is not empty
if [ -z "$latest_version" ]; then
    echo "Error: Could not determine latest version."
    exit 1
fi

# Update the plugin.php file
# This sed command is tailored for the $version assignment line as shown in your plugin.php
sed -i "s/\$version = '[^']*';/\$version = '$latest_version';/" plugin.php

# Check if plugin.php was updated
if ! git diff --exit-code --quiet plugin.php; then
    # Configure Git (this should match the configuration used for other git operations within your actions)
    git config --global user.email "email@domain.com"
    git config --global user.name "Max"

    # Add and commit changes
    git add plugin.php
    git commit -m "chore: update version in plugin.php to $latest_version"
else
    echo "No changes to plugin.php"
fi
