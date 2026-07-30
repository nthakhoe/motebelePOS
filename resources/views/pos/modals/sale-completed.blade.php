@if($showSaleCompletedModal)

<div class="sale-modal-overlay">

    <div class="sale-modal">

        <div class="sale-modal-header">
            <h2>✓ Payment Successful</h2>
        </div>

        <div class="sale-modal-body">

            <div class="sale-row">
                <span>Sale Number</span>
                <strong>{{ data_get($completedSale,'sale_number') }}</strong>
            </div>

            <div class="sale-row">
                <span>Receipt</span>
                <strong>{{ data_get($completedSale,'receipt_no','Pending Fiscalisation') }}</strong>
            </div>

            <div class="sale-row">
                <span>Amount Due</span>
                <strong>M {{ number_format(data_get($completedSale,'total',0),2) }}</strong>
            </div>

            <div class="sale-row">
                <span>Amount Paid</span>
                <strong>M {{ number_format(data_get($completedSale,'paid',0),2) }}</strong>
            </div>

            <div class="sale-row sale-change">
                <span>Change</span>
                <span>M {{ number_format(data_get($completedSale,'change',0),2) }}</span>
            </div>

        </div>

        <div class="sale-modal-footer">

            <button
                wire:click="printReceipt"
                class="sale-btn sale-btn-print">

                🖨 Print Receipt

            </button>

            <button
                wire:click="closeSaleCompletedModal"
                class="sale-btn sale-btn-new">

                New Sale

            </button>

        </div>

    </div>

</div>

@endif