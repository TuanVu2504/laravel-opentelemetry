<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Facade;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Instrumentation\Configurator;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextStorage;
use OpenTelemetry\Contrib\Context\Swoole\SwooleContextStorage;
use OpenTelemetry\Contrib\Grpc\GrpcTransportFactory;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

require __DIR__ . '/vendor/autoload.php';

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $transport = (new GrpcTransportFactory())->create('http://127.0.0.1:4317');
        $exporter = new SpanExporter($transport);
        $spanProcessor = new SimpleSpanProcessor($exporter);
        $tracerProvider = new TracerProvider($spanProcessor);

        // Use Swoole context storage
        Context::setStorage(new SwooleContextStorage(new ContextStorage()));

        // Register the tracer provider
        Globals::registerInitializer(fn (Configurator $configurator) => $configurator->withTracerProvider($tracerProvider));

        $tracer = Globals::tracerProvider()->getTracer('io.opentelemetry.contrib.swoole.php');

        try {
            $root = $tracer->spanBuilder(\Request::getRequestUri())
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
            $scope = $root->activate();

            for ($i = 0;
                $i < 3;
                $i++
            ) {
                // start a span, register some events
                $span = $tracer->spanBuilder('loop-' . $i)->startSpan();

                $span
                    ->setAttribute('remote_ip', '1.2.3.4')
                    ->setAttribute('country', 'USA');

                $span->addEvent('found_login' . $i, [
                    'id' => $i,
                    'username' => 'otuser' . $i,
                ]);
                $span->addEvent('generated_session', [
                    'id' => md5((string) microtime(true)),
                ]);

                $span->end();
            }
        } finally {
            $root->end();
            $scope->detach();
        }
    }
}
