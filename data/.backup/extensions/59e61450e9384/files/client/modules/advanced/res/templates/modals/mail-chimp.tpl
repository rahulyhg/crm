{{#each fieldData}}
    <div class="row">    
        <div class="cell cell-{{@key}} form-group col-sm-6">
            <label class="control-label">{{prop this 'parentLabel'}}</label>
            <div class="field field-{{this}}"> 
                <a href="#{{prop this 'parentType'}}/view/{{prop this 'parentId'}}">{{prop this 'parentName'}}</a> 
            </div>
        </div> 
        
        <div class="cell cell-{{@key}} col-sm-6 form-group">
            <label class="field-label-{{@key}} control-label">
                {{prop this 'label'}}
            </label>
            <div class="field field-{{@key}}">
               {{{var @key ../this}}}
            </div>
        </div>
    </div>    
{{/each}}
{{#if hasFooter}}
    <div class='mc-dialog-footer'>
    {{{dialogFooter}}}
    </div>
{{/if}}
