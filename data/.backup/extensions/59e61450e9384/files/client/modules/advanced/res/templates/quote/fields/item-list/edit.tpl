
<div class="item-list-container list no-side-margin">{{{itemList}}}</div>
<div class="button-container">
    <button class="btn btn-default" data-action="addItem" title="{{translate 'Add Item' scope='Opportunity'}}"><span class="glyphicon glyphicon-plus"></span></button>
</div>
<div class="row{{#unless showFields}} hidden{{/unless}} totals-row">
    <div class="cell cell-currency col-sm-offset-7 col-sm-5 form-group">
        <label class="field-label-currency control-label">
            {{translate 'currency' category='fields' scope='Quote'}}
        </label>
        <div class="field-currency">{{{currency}}}</div>
    </div>

    <div class="cell cell-preDiscountedAmount col-sm-offset-7 col-sm-5 form-group">
        <div class="clearfix">
            <label class="field-label-preDiscountedAmount control-label">
                {{translate 'preDiscountedAmount' category='fields' scope='Quote'}}
            </label>
        </div>
        <div class="clearfix">
            <div class="field-preDiscountedAmount">
                {{{preDiscountedAmount}}}
            </div>
        </div>
    </div>
    <div class="cell cell-discountAmount col-sm-offset-7 col-sm-5 form-group">
        <div class="clearfix">
            <label class="field-label-discountAmount control-label">
                {{translate 'discountAmount' category='fields' scope='Quote'}}
            </label>
        </div>
        <div class="clearfix">
            <div class="field-discountAmount">
                {{{discountAmount}}}
            </div>
        </div>
    </div>
    <div class="cell cell-amount col-sm-offset-7 col-sm-5 form-group">
        <div class="clearfix">
            <label class="field-label-amount-bottom control-label">
                {{translate 'amount' category='fields' scope='Quote'}}
            </label>
        </div>
        <div class="clearfix">
            <div class="field-amount-bottom">
                {{{amount}}}
            </div>
        </div>
    </div>
    <div class="cell cell-taxAmount col-sm-offset-7 col-sm-5 form-group">
        <div class="clearfix">
            <label class="field-label-taxAmount control-label">
                {{translate 'taxAmount' category='fields' scope='Quote'}}
            </label>
        </div>
        <div class="clearfix">
            <div class="field-taxAmount">
                {{{taxAmount}}}
            </div>
        </div>
    </div>
    <div class="cell cell-shippingCost col-sm-offset-7 col-sm-5 form-group">
        <div class="clearfix">
            <label class="field-label-shippingCost control-label">
                {{translate 'shippingCost' category='fields' scope='Quote'}}
            </label>
        </div>
        <div class="clearfix">
            <div class="field-shippingCost">
                {{{shippingCost}}}
            </div>
        </div>
    </div>
    <div class="cell cell-grandTotalAmount col-sm-offset-7 col-sm-5 form-group">
        <div class="clearfix">
            <label class="field-label-grandTotalAmount control-label">
                {{translate 'grandTotalAmount' category='fields' scope='Quote'}}
            </label>
        </div>
        <div class="clearfix">
            <div class="field-grandTotalAmount">
                {{{grandTotalAmount}}}
            </div>
        </div>
    </div>
</div>