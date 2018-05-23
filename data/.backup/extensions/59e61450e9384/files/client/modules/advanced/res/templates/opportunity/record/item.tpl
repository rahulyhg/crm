
<td>
    <div class="field field-name">
        {{{name}}}
    </div>
    <div class="field field-description small">
        {{{description}}}
    </div>
</td>
<td width="10%">
    <div class="field field-quantity">
        {{{quantity}}}
    </div>
</td>
<td width="20%">
    <div class="field field-unitPrice">
        {{{unitPrice}}}
    </div>
</td>
<td width="20%">
    <div class="field field-itemAmount pull-right{{#ifEqual mode 'edit'}} detail-field-container{{/ifEqual}}">
        {{{amount}}}
    </div>
</div>
<td width="{{#ifEqual mode 'edit'}}60{{else}}1{{/ifEqual}}">
    <div class="{{#ifEqual mode 'edit'}} detail-field-container{{/ifEqual}}">
        {{#ifEqual mode 'edit'}}
        <span class="glyphicon glyphicon-magnet drag-icon text-muted" style="cursor: pointer;"></span>
        <a href="javascript:" class="pull-right" data-action="removeItem" data-id="{{id}}" title="{{translate 'Remove'}}"><span class="glyphicon glyphicon-remove"></span></a>
        {{/ifEqual}}
    </div>
</td>

