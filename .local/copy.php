<?php

function copyDirectoryRecursiveWithExclusions( string $source, string $destination, array $excludePatterns = array() ): bool {
	// Ensure source directory exists and is a directory
	if ( ! is_dir( $source ) ) {
		return false;
	}

	// Create destination directory if it doesn't exist
	if ( ! is_dir( $destination ) ) {
		if ( ! mkdir( $destination, 0777, true ) ) { // Adjust permissions as needed
			return false;
		}
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $source, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$relativePath = $iterator->getSubPathName();
		$sourcePath = $item->getPathname();
		$destinationPath = $destination . DIRECTORY_SEPARATOR . $relativePath;

		// Check for exclusion patterns
		$skipItem = false;
		foreach ( $excludePatterns as $pattern ) {
			// Use regex or string matching for exclusion
			if ( preg_match( $pattern, $relativePath ) ) { // Example: regex matching
				$skipItem = true;
				break;
			}
		}

		if ( $skipItem ) {
			continue; // Skip this item
		}

		if ( $item->isDir() ) {
			if ( ! is_dir( $destinationPath ) ) {
				if ( ! mkdir( $destinationPath, 0777, true ) ) {
					return false;
				}
			}
		} elseif ( $item->isFile() ) {
			if ( ! copy( $sourcePath, $destinationPath ) ) {
				return false;
			}
		}
	}

	return true;
}

// Example usage:
$sourceDir = '/workdir/wp';
$destinationDir = '/workdir/dist';
$exclude = array(
	'/tests/',
	'/tools/',
	'/node_modules/',
	'/logs/',
	'/src/',
	'/^\./',
	'/^php/',
	'/^composer/',
	'/^Dockerfile/',
	'/^package/',
	'/^vite./',
	'/^tsconfig/',
	'/^eslint/',
	'/\.md$/',
	'/public\/build/',
);

if ( copyDirectoryRecursiveWithExclusions( $sourceDir, $destinationDir, $exclude ) ) {
	echo "Folder copied successfully, with exclusions.\n";
} else {
	echo "Error copying folder.\n";
}
