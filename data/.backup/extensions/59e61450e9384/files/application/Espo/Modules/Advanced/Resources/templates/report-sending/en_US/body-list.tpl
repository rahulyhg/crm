{{#if description}}<p>{{{description}}}</p>{{/if}}
{{#if name}}<p>{{name}}</p>{{/if}}
{{#if header}}<p>{{header}}</p>{{/if}}

{{#if rows}}
<table cellspacing="0" cellpadding="5" border="1">
    {{#if columnList}}
        <tr>
        {{#each columnList}}
            <th {{#if attrs.width}} width="{{attrs.width}}" {{/if}}
                {{#if attrs.align}} align="{{attrs.align}}" {{/if}}
            >
                <b>{{label}}</b>
           </th>
        {{/each}}
        </tr>
    {{/if}}

    {{#each rows}}
        <tr>
        {{#each .}}
            <td {{#if attrs.align}} align="{{attrs.align}}" {{/if}}>
                {{#if wrapper}}
                    <{{wrapper}}>
                {{/if}}
                {{value}}
                {{#if wrapper}}
                    </{{wrapper}}>
                {{/if}}
            </td>
        {{/each}}
        </tr>
    {{/each}}
</table>
{{else}}
    {{NoData}}
{{/if}}
