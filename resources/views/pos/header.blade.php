<div class="pos-header">

    <div class="header-left">

        <img
            src="{{ asset('images/logo.png') }}"
            alt="Motebele Systems"
            class="pos-logo"
        >

        <div class="company-info">
            <h2>Motebele POS</h2>
            <small>Main Register</small>
        </div>

    </div>

    <div class="header-search">

        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="🔍 Scan barcode or search products..."
        >

    </div>

    <div class="header-right">

        <div class="header-user">

            <strong>{{ auth()->user()->name }}</strong>

            <small>Cashier</small>

        </div>

        <div class="header-time">

            <span id="currentTime"></span>

            <small id="currentDate"></small>

        </div>

    </div>

</div>

<script>
setInterval(function(){

    const now = new Date();

    document.getElementById('currentTime').innerHTML =
        now.toLocaleTimeString([], {
            hour:'2-digit',
            minute:'2-digit'
        });

    document.getElementById('currentDate').innerHTML =
        now.toLocaleDateString();

},1000);
</script>