<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Contrib\Zipkin\Exporter as ZipkinExporter;
use OpenTelemetry\SDK\Common\Export\Http\PsrTransportFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextStorage;
use OpenTelemetry\Contrib\Context\Swoole\SwooleContextStorage;
use OpenTelemetry\Contrib\Grpc\GrpcTransportFactory;
use OpenTelemetry\Contrib\Otlp\OtlpUtil;
use OpenTelemetry\API\Signals;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;


define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

Log::info("Run 1");
$httpClient = new Client();
$httpFactory = new HttpFactory();

$transport = (new GrpcTransportFactory())
    ->create(
                env('OTEL_EXPORTER_OTLP_ENDPOINT', 'http://otel:4317') . OtlpUtil::method(Signals::TRACE)
            );
$exporter = new SpanExporter($transport);
$spanProcessor = new SimpleSpanProcessor($exporter);
$tracerProvider = new TracerProvider($spanProcessor);

// Use Swoole context storage
Context::setStorage(new SwooleContextStorage(new ContextStorage()));
Globals::registerInitializer(fn (Configurator $configurator) => $configurator->withTracerProvider($tracerProvider));

$tracer = Globals::tracerProvider()->getTracer('Hello World Laravel Web Server');

$request = Request::capture();
$span = $tracer->spanBuilder($request->url())->startSpan();
$spanScope = $span->activate();


$app = require_once __DIR__.'/../bootstrap/app.php';
Log::info("Run 2");

$kernel = $app->make(Kernel::class);
Log::info("Run 3");

$response = $kernel->handle(
    $request = Request::capture()
)->send();
Log::info("Run 4");

$kernel->terminate($request, $response);
Log::info("Run 5");

$span->end();
$spanScope->detach();
Log::info("Run 6");