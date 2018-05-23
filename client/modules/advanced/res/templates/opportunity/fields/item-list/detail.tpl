{{#if isEmpty}}
    {{#ifNotEqual mode 'edit'}}
        <div>{{translate 'None'}}</div>
    {{/ifNotEqual}}
{{/if}}

<div class="item-list-container list no-side-margin">{{{itemList}}}</div>