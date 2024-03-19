<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;

class TelemetryController extends Controller
{
    public function index(){
        $tracer = Globals::tracerProvider()->getTracer('TestTelemetry');
        $root = $tracer->spanBuilder('TelemetryController')
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->startSpan();
        $scope = $root->activate();

        $root->setAttribute('foo', 'bar');
        $root->setAttribute('Application', 'Laravel');
        $root->setAttribute('foo', 'bar1');
        $root->updateName('New name');

        $root->end();
        $scope->detach();
        return "Telemetry Route reached";
    }
}
