
<td>
    <div class="field field-name">
        {{{name}}}
    </div>
    <div class="field field-description small">
        {{{description}}}
    </div>
</td>
<td width="9%">
    <div class="field field-quantity">
        {{{quantity}}}
    </div>
</td>
{{#unless hideTaxRate}}
<td width="10%">
    <div class="field field-taxRate">
        {{{taxRate}}}
    </div>
</td>
{{/unless}}
<td width="15%">
    <div class="field field-listPrice">
        {{{listPrice}}}
    </div>
</td>
<td width="15%">
    <div class="field field-unitPrice">
        {{{unitPrice}}}
    </div>
</td>
<td width="15%">
    <div class="field field-amount pull-right{{#ifEqual mode 'edit'}} detail-field-container{{/ifEqual}}">
        {{{amount}}}
    </div>
</td>
<td width="{{#ifEqual mode 'edit'}}55{{else}}1{{/ifEqual}}">
    <div class="{{#ifEqual mode 'edit'}} detail-field-container{{/ifEqual}}">
        {{#ifEqual mode 'edit'}}
        <a href="javascript:" class="pull-right" data-action="removeItem" data-id="{{id}}" title="{{translate 'Remove'}}"><span class="glyphicon glyphicon-remove"></span></a>
        <span class="glyphicon glyphicon-magnet drag-icon text-muted" style="cursor: pointer;"></span>
        {{/ifEqual}}
    </div>
</td>
{{#if showRowActions}}
<td class="cell" data-name="buttons">
    {{{rowActions}}}
</td>
{{/if}}

