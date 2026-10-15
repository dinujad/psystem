@if(!empty($myWork))
<script type="text/javascript">
    (function () {
        var box = document.getElementById('home_my_work');
        if (!box) { return; }
        function pad(n) { return n < 10 ? '0' + n : '' + n; }
        function clock(seconds) {
            if (seconds < 0) { seconds = 0; }
            return pad(Math.floor(seconds / 3600)) + ':' + pad(Math.floor((seconds % 3600) / 60)) + ':' + pad(seconds % 60);
        }
        function hours(seconds) {
            if (seconds < 0) { seconds = 0; }
            var h = Math.floor(seconds / 3600);
            var m = Math.floor((seconds % 3600) / 60);
            return h + 'h ' + m + 'm';
        }
        function tick() {
            var status = box.getAttribute('data-status');
            var startRaw = box.getAttribute('data-clock-in');
            var endRaw = box.getAttribute('data-clock-out');
            var weekClosed = parseInt(box.getAttribute('data-week') || '0', 10);
            var monthClosed = parseInt(box.getAttribute('data-month') || '0', 10);
            var todaySeconds = 0;
            if (startRaw) {
                var start = new Date(String(startRaw).replace(' ', 'T'));
                var end = status === 'in' ? new Date() : (endRaw ? new Date(String(endRaw).replace(' ', 'T')) : new Date());
                todaySeconds = Math.floor((end.getTime() - start.getTime()) / 1000);
            }
            var live = status === 'in' ? todaySeconds : 0;
            document.getElementById('home_today_timer').textContent = clock(todaySeconds);
            document.getElementById('home_week_time').textContent = hours(weekClosed + live);
            document.getElementById('home_month_time').textContent = hours(monthClosed + live);
        }
        tick();
        setInterval(tick, 1000);

        function mark(url, btn) {
            btn.prop('disabled', true);
            function send(extra) {
                $.post(url, $.extend({
                    user_id: box.getAttribute('data-user'),
                    _token: '{{ csrf_token() }}'
                }, extra), function (result) {
                    if (result.success) {
                        if (typeof toastr !== 'undefined') { toastr.success(result.msg); }
                        window.location.reload();
                    } else {
                        if (typeof toastr !== 'undefined') { toastr.error(result.msg); }
                        btn.prop('disabled', false);
                    }
                }).fail(function () {
                    if (typeof toastr !== 'undefined') { toastr.error('Could not mark attendance.'); }
                    btn.prop('disabled', false);
                });
            }
            if (box.getAttribute('data-require') !== '1') {
                send({});
                return;
            }
            if (!navigator.geolocation) {
                if (typeof toastr !== 'undefined') { toastr.error('This browser cannot read your location.'); }
                btn.prop('disabled', false);
                return;
            }
            navigator.geolocation.getCurrentPosition(function (position) {
                send({ latitude: position.coords.latitude, longitude: position.coords.longitude });
            }, function () {
                if (typeof toastr !== 'undefined') { toastr.error('Allow location. You can mark attendance only within 100m of the office.'); }
                btn.prop('disabled', false);
            }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
        }
        $('#home_attend_btn').on('click', function () {
            mark("{{ url('hrm/attendance/today-arrive') }}", $(this));
        });
        $('#home_out_btn').on('click', function () {
            mark("{{ url('hrm/attendance/today-out') }}", $(this));
        });
    })();
</script>
@endif
