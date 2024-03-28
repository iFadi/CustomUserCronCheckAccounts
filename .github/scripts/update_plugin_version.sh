#!/bin/bash

echo "Running update_plugin_version.sh..."

# Fetch the latest tag and format it.
latest_version=$(git describe --tags `git rev-list --tags --max-count=1`)
formatted_version=${latest_version#v}
echo "Latest version determined: $latest_version"
echo "Formatted version for update: $formatted_version"

# Display the current version line for diagnostics.
echo "Current version line in plugin.php:"
grep "\$version" plugin.php

# Attempt to update plugin.php with the new version.
echo "Attempting to update plugin.php with latest version..."
sed -i "s/\$version = \"[^\"]*\";/\$version = \"$formatted_version\";/" plugin.php

echo "Updated version line in plugin.php:"
grep "\$version" plugin.php

# Verify if changes were made and commit if so.
if ! git diff --exit-code --quiet plugin.php; then
    echo "plugin.php was updated. Proceeding with commit..."

    git config user.email "asbih@elsa.uni-hannover.de"
    git config user.name "Fadi Asbih"

    git add plugin.php
    git commit -m "chore: update version in plugin.php to $formatted_version"
else
    echo "No changes to plugin.php detected."
fi
