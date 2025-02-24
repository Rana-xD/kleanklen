<?php

namespace Botble\Base\Http\Controllers;

use Botble\Base\Http\Controllers\Concerns\HasBreadcrumb;
use Botble\Base\Http\Controllers\Concerns\HasHttpResponse;
use Botble\Base\Http\Controllers\Concerns\HasPageTitle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

/**
 * Base Controller class that all other controllers should extend
 * 
 * This controller provides common functionality used across the application including:
 * - Breadcrumb management (HasBreadcrumb trait)
 * - HTTP response handling (HasHttpResponse trait) 
 * - Page title management (HasPageTitle trait)
 * - Authorization capabilities (AuthorizesRequests trait)
 * - Job dispatching (DispatchesJobs trait)
 * - Request validation (ValidatesRequests trait)
 */
class BaseController extends Controller
{
    use HasBreadcrumb;
    use HasHttpResponse; 
    use HasPageTitle;
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
}
