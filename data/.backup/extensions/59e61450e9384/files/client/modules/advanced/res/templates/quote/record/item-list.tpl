
{{#if itemDataList.length}}
<table class="table less-padding">
<thead>
<tr>
    <th>
        <label>
            {{translate 'name' category='fields' scope='QuoteItem'}}
        </label>
    </th>
    <th width="9%">
        <label>
            {{translate 'qty' category='fields' scope='QuoteItem'}}
        </label>
    </div>
    {{#unless hideTaxRate}}
    <th width="10%">
        <label>
            {{translate 'taxRate' category='fields' scope='QuoteItem'}}
        </label>
    </div>
    {{/unless}}
    <th width="15%">
        <label>
            {{translate 'listPrice' category='fields' scope='QuoteItem'}}
        </label>
    </th>
    <th width="15%">
        <label>
            {{translate 'unitPrice' category='fields' scope='QuoteItem'}}
        </label>
    </th>
    <th width="15%">
        <label class="pull-right">
            {{translate 'amount' category='fields' scope='QuoteItem'}}
        </label>
    </th>
    <th width="{{#ifEqual mode 'edit'}}55{{else}}1{{/ifEqual}}">
        &nbsp;
    </th>
    {{#if showRowActions}}
    <td width="25">
       &nbsp;
    </td>
    {{/if}}
</tr>
</thead>

<tbody class="item-list-internal-container">
{{#each itemDataList}}
    <tr class="item-container-{{id}}" data-id="{{id}}">
    {{{var key ../this}}}
    </tr>
{{/each}}
</tbody>
</table>
{{/if}}