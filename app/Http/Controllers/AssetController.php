<?php

namespace App\Http\Controllers;

use Exception;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Flysystem\Filesystem;
use App\Http\Requests\AssetFileRequest;
use Illuminate\Support\Facades\Storage;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Glide\Filesystem\FileNotFoundException;
use League\Glide\Responses\LaravelResponseFactory;
use League\Glide\Responses\SymfonyResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetController extends Controller
{
    protected Server $server;

    public function __construct()
    {
        $this->server = ServerFactory::create([
            'response' => new SymfonyResponseFactory(app('request')),
            // Must match config('assets.disk'), which is where uploads land.
            'source' => Storage::disk(config('assets.disk'))->getDriver(),
            'cache' => Storage::disk('local')->getDriver(),
            'cache_path_prefix' => '.glide-cache/',
            'base_url' => 'assets',
            'defaults' => [
                'q' => '75',
            ],
            'disable_asserts' => true,
        ]);
    }

    public function __invoke(AssetFileRequest $request, string $path): StreamedResponse
    {
        try {
            $path = $request->path;

            // Validate HTTP signature
            // Use the $factory->generateSignature(path, params) method to generate signature
            $f = SignatureFactory::create(config('app.key'));
            //dd($f->generateSignature("/assets/$path", $request->all()));
            $f->validateRequest("/assets/$path", $request->all());

            return $this->getImageResponse($path, $request->all());
        } catch (SignatureException $e) {
            // Forbidden
            abort(403);
        } catch (FileNotFoundException $e) {
            // Not Found
            abort(404);
        }
    }

    public function getPlaceholder(string $path): string
    {
        return $this->server->getImageAsBase64($path, $this->placeholderParams);
    }

    public function getImageResponse(string $path, array $params)
    {
        try {
            return $this->server->getImageResponse($path, $params);
        } catch (Exception $e) {
            abort(404);
        }
    }
}
