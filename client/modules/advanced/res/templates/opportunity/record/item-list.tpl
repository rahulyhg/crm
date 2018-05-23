
{{#if itemDataList.length}}
<table class="table less-padding">
<thead>
<tr>
    <th>
        <label>
            {{translate 'name' category='fields' scope='OpportunityItem'}}
        </label>
    </th>
    <th width="10%">
        <label>
            {{translate 'qty' category='fields' scope='OpportunityItem'}}
        </label>
    </th>
    <th width="20%">
        <label>
            {{translate 'unitPrice' category='fields' scope='OpportunityItem'}}
        </label>
    </th>
    <th width="20%">
        <label class="pull-right">
            {{translate 'amount' category='fields' scope='OpportunityItem'}}
        </label>
    </th>
    <th width="{{#ifEqual mode 'edit'}}60{{else}}1{{/ifEqual}}">
        &nbsp;
    </th>
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