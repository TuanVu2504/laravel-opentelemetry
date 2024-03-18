<?php
namespace App\Http\Controllers;

use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Globals;
use Illuminate\Support\Facades\Log;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Trace\TracerProvider;

use Illuminate\Http\Request;

class HelloController extends Controller
{
    public function index(){
        Log::info("Reached");
        $tracer = Globals::tracerProvider()->getTracer('HelloController');
        if ($tracer) {
            $root = $tracer->spanBuilder('HelloController')
                ->startSpan();
            $scope = $root->activate();

            $span = Span::getCurrent();

            $span->setAttribute('foo', 'bar');
            $span->setAttribute('Application', 'Laravel');
            $span->setAttribute('foo', 'bar1');
            $span->updateName('HelloController');

            $childSpan = $tracer->spanBuilder('Child span')->startSpan();
            $childScope = $childSpan->activate();
            try {
                throw new \Exception('Exception Example');
            } catch (\Exception $exception) {
                $childSpan->recordException($exception);
            }
            $childSpan->end();
            $childScope->detach();

            $root->end();
            $scope->detach();

            return "helloController";
        }
    }
}
