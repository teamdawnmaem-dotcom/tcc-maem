
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-end mb-3">
        <div>
            <h2 class="mb-1">Recognition Monitoring</h2>
            <div class="text-muted small">Checks every <strong>15 seconds</strong>. Only the latest new record is pushed.</div>
        </div>
        <div class="text-end small">
            Last Pushed Log ID: <span id="lastId">0</span><br>
            Last Status: <span id="lastStatus">—</span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Latest Record (pushed)</div>
        <div class="card-body">
            <div id="latestBox" class="text-muted">Waiting for first poll…</div>
        </div>
    </div>
</div>


<script>
(function(){
    const lastIdEl   = document.getElementById('lastId');
    const lastStatus = document.getElementById('lastStatus');
    const latestBox  = document.getElementById('latestBox');
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    let lastId = 0; // last pushed log_id

    async function pollAndPush() {
        try {
            const res = await fetch(`{{ route('monitoring.pollpush') }}?since_id=${lastId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                }
            });

            const data = await res.json().catch(() => ({}));

            if (data.has_new) {
                lastId = data.last_id || lastId;
                lastIdEl.textContent = lastId;

                const p = data.payload || {};
                latestBox.innerHTML = `
                    <div><strong>Faculty:</strong> ${p.faculty_name ?? ''}</div>
                    <div><strong>Status:</strong> ${p.status ?? ''}</div>
                    <div><strong>Time:</strong> ${p.recognition_time ?? ''}</div>
                    <div><strong>Camera:</strong> ${p.camera_name ?? ''} (ID: ${p.camera_id ?? ''})</div>
                    <div><strong>Room:</strong> ${p.room_name ?? ''}, <strong>Building:</strong> ${p.building_no ?? ''}</div>
                    <div><strong>Faculty ID:</strong> ${p.faculty_id ?? ''}, <strong>Load ID:</strong> ${p.teaching_load_id ?? ''}</div>
                    <div><strong>Distance:</strong> ${p.distance ?? ''} m</div>
                `;
                lastStatus.textContent = data.pushed_ok ? `Pushed (HTTP ${data.status})` : `Failed (HTTP ${data.status})`;
                lastStatus.className = data.pushed_ok ? 'text-success' : 'text-danger';
            } else {
                lastStatus.textContent = 'No new data';
                lastStatus.className = 'text-muted';
            }
        } catch (e) {
            lastStatus.textContent = 'Error polling/pushing';
            lastStatus.className = 'text-danger';
        }
    }

    // run now, then every 15s
    pollAndPush();
    setInterval(pollAndPush, 10000);
})();
</script>
