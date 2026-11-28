@if(auth()->check())
<script>
    (() => {
        const currentUserId = {{ (int) auth()->id() }};
        const currentRole = @json(auth()->user()->isAdmin() ? 'admin' : 'student');
        const channelName = `ob_auth_sync_${window.location.host}`;
        const storageKey = `ob_auth_state_${window.location.host}`;
        const dashboardUrl = @json(route('dashboard'));
        let redirecting = false;

        const handleAuthPayload = (payload) => {
            if (!payload || redirecting) return;
            if (payload.userId !== currentUserId || payload.role !== currentRole) {
                redirecting = true;
                window.location.replace(dashboardUrl);
            }
        };

        if (typeof BroadcastChannel === 'function') {
            const authChannel = new BroadcastChannel(channelName);
            authChannel.onmessage = (event) => handleAuthPayload(event.data);
            authChannel.postMessage({ userId: currentUserId, role: currentRole });
        }

        window.addEventListener('storage', (event) => {
            if (event.key !== storageKey || !event.newValue) return;
            try {
                handleAuthPayload(JSON.parse(event.newValue));
            } catch (e) {}
        });

        try {
            localStorage.setItem(storageKey, JSON.stringify({
                userId: currentUserId,
                role: currentRole,
                ts: Date.now()
            }));
        } catch (e) {}
    })();
</script>
@endif
