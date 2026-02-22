@extends('layouts/layoutMaster')

@section('title', 'System Status & Settings')

@section('content')
    <h4 class="py-3 mb-4">
        <span class="text-muted fw-light">Settings /</span> System Status
    </h4>

    <div class="row">
        <div class="col-md-6 col-12 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Manual Actions</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Run checking jobs manually if the automated background process has not run.</p>

                    <form action="{{ route('admin.system-status.run-auto-clock-out') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary d-grid w-100">
                            <span class="d-flex align-items-center justify-content-center text-nowrap">
                                <i class="ti ti-clock-stop me-2"></i>
                                Run Auto Clock-Out Job
                            </span>
                        </button>
                    </form>
                    <div class="mt-3 small text-muted">
                        Executing this logic will:
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Close all class sessions that ended >30 mins ago.</li>
                            <li>Auto-clock out students who forgot (if required).</li>
                            <li>Send notification emails to affected students.</li>
                        </ul>
                    </div>

                    <hr class="my-3">

                    <form action="{{ route('admin.system-status.run-mark-absent') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger d-grid w-100">
                            <span class="d-flex align-items-center justify-content-center text-nowrap">
                                <i class="ti ti-user-x me-2"></i>
                                Run Absenteeism Check
                            </span>
                        </button>
                    </form>
                    <div class="mt-2 small text-muted">
                        Checks for past classes with no attendance and marks students as <strong>Absent</strong>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-12 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Server Configuration</h5>
            </div>
            <div class="card-body">
                <p class="small text-muted">To automate these actions, configure a Cron Job on your server.</p>

                <div class="alert alert-secondary">
                    <strong>Cron Command:</strong>
                    <br>
                    <code class="user-select-all">cd {{ base_path() }} && php artisan schedule:run >> /dev/null
                        2>&1</code>
                </div>

                <div class="accordion mt-3" id="serverInstructions">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne">
                                CWP (CentOS Web Panel) Instructions
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#serverInstructions">
                            <div class="accordion-body small">
                                1. Log in to CWP Admin/User Panel.<br>
                                2. Go to <strong>Server Settings</strong> > <strong>Cron Job Manager</strong>.<br>
                                3. Add a new Cron Job.<br>
                                4. Frequency: <strong>Every Minute</strong> (<code>* * * * *</code>).<br>
                                5. Command: Paste the command above.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseTwo">
                                cPanel Instructions
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#serverInstructions">
                            <div class="accordion-body small">
                                1. Log in to cPanel.<br>
                                2. Go to <strong>Cron Jobs</strong>.<br>
                                3. Add New Cron Job.<br>
                                4. Common Settings: <strong>Once Per Minute</strong>.<br>
                                5. Command: Paste the command above.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
@endsection
