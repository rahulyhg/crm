{{#if readOnly}}
    {{{listHtml}}}
{{else}}
    <select name="subject" class="form-control input-sm">
        {{{listHtml}}}
    </select>
{{/if}}