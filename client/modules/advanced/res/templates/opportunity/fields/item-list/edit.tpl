

<div class="item-list-container list no-side-margin">{{{itemList}}}</div>
<div class="button-container">
    <button class="btn btn-default" data-action="addItem" title="{{translate 'Add Item' scope='Opportunity'}}"><span class="glyphicon glyphicon-plus"></span></button>
</div>
<div class="row {{#unless showCurrency}} hidden{{/unless}}">
    <div class="cell cell-currency col-md-2 col-sm-3 col-xs-6">
        <label class="field-label-currency control-label">
            {{translate 'currency' category='fields' scope='Quote'}}
        </label>
        <div class="field-currency">{{{currency}}}</div>
    </div>
</div>