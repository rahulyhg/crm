{{#if description}}<p>{{{description}}}</p>{{/if}}
{{#if name}}<p>{{name}}</p>{{/if}}

{{#each grids}}
    {{#if header}}<p>{{header}}</p>{{/if}}
    <table cellspacing="0" cellpadding="5" border="1">
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
                {{/if}}</td>
            {{/each}}
            </tr>
        {{/each}}
    </table>
    <p>&nbsp;</p>
{{/each}}
