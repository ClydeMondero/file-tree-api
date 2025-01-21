<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use CzProject\GitPhp\Git;
use Illuminate\Support\Facades\File;


class TreeController extends Controller
{

    public function getFileTree(Request $request)
    {
        $url = $request->query('url');

        // Remove the .git extension (optional)
        $urlWithoutGit = preg_replace('/\.git$/', '', $url);

        // Extract the repository name using basename()
        $repositoryName = basename($urlWithoutGit);

        $git = new Git();

        $repo = $git->cloneRepository($url);


        $path = $repo->getRepositoryPath();

        $tree = $this->getDirectoryTree($path);

        $formattedTree = implode("\n", $tree);

        defer(fn() => $this->deleteRepository($path));

        return response()->json([
            "path" => $path,
            "tree" => $formattedTree,
            "success" => true,
            "repoName" => $repositoryName
        ]);
    }

    private function deleteRepository($directory)
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = array_diff(scandir($directory), ['.', '..']);

        foreach ($items as $item) {
            $itemPath = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($itemPath)) {
                // Recursively delete subdirectory
                $this->deleteRepository($itemPath);
            } else {
                // Delete file
                unlink($itemPath);
            }
        }

        // Remove the now-empty directory
        rmdir($directory);
    }

    private function getDirectoryTree($dir, $prefix = '')
    {
        $result = [];

        // Get all files in the current directory
        $files = File::files($dir); // Only get files in this directory

        // Add files to the result with indentation
        foreach ($files as $file) {
            $relativePath = str_replace(public_path() . '/', '', $file->getPathname());
            $result[] = $prefix . basename($relativePath); // Add file name with indentation
        }

        // Get all directories in the current directory
        $directories = File::directories($dir);

        // Add directories and their contents recursively
        foreach ($directories as $directory) {
            $dirName = basename($directory); // Get only the directory name
            $result[] = $prefix . $dirName; // Add directory name with current indentation
            $subDirTree = $this->getDirectoryTree($directory, $prefix . '    '); // Increase prefix for sub-items
            $result = array_merge($result, $subDirTree);
        }


        return $result;
    }
}
