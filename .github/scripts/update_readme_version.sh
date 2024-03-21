#!/bin/bash

# Get the latest version (assuming it's stored in a file or as a Git tag)
latest_version=$(git describe --tags `git rev-list --tags --max-count=1`)

# Check if latest_version is not empty
if [ -z "$latest_version" ]; then
    echo "Error: Could not determine latest version."
    exit 1
fi

# Update the README.md file
sed -i "/^## Version/{N;s/.*/## Version\n$latest_version/}" README.md


# Check if README was updated
if ! git diff --exit-code --quiet README.md; then
    # Configure Git (Replace with your details)
    git config --global user.email "fadi@asbih.com"
    git config --global user.name "Fadi Asbih"

    # Add and commit changes
    git add README.md
    git commit -m "chore: update version in README to $latest_version"
else
    echo "No changes to README.md"
fi
