#!/bin/bash

echo "Running update_plugin_version.sh..."
latest_version=$(git describe --tags $(git rev-list --tags --max-count=1))
echo "Latest version determined: $latest_version"
formatted_version=${latest_version#v}
echo "Formatted version for update: $formatted_version"
echo "Current version line in plugin.php:"
grep "\$version" plugin.php

# Attempting to update plugin.php with latest version, adding verbose output
sed -i "s/\$version = '[^']*';/\$version = '$formatted_version';/" plugin.php
echo "Attempted to update plugin.php to version $formatted_version."

# Outputting the version line again to verify changes
echo "Updated version line in plugin.php:"
grep "\$version" plugin.php

# Check if plugin.php was updated
if ! git diff --exit-code --quiet plugin.php; then
    echo "plugin.php was updated. Proceeding with commit..."
    git config --global user.email "fadi@asbih.com"
    git config --global user.name "Fadi Asbih"
    git add plugin.php
    git commit -m "chore: update version in plugin.php to $formatted_version"
else
    echo "No changes to plugin.php detected."
fi
