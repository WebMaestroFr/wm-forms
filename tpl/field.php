<dl>
  <dt>
    <span class="item-title"><%= label %></span>
    <span class="item-controls">
      <span class="item-type"><%= type.charAt(0).toUpperCase() + type.slice(1) %></span>
      <a class="item-edit" href="#"></a>
    </span>
  </dt>
  <dd>
    <p class="description">
      <label>
        <?php _e( 'Field Label', 'wm_forms' ); ?><br />
        <input type="text" class="widefat wm-form-field-label" value="<%= label %>">
      </label>
    </p>
    <p class="description">
      <label>
        <input type="checkbox" class="wm-form-field-required"<% if (required) { %> checked<% } %>> <?php _e( 'Required', 'wm_forms' ); ?>
      </label>
    </p>
    <p class="description">
      <label>
        <?php _e( 'Type', 'wm_forms' ); ?><br />
        <select class="widefat wm-form-field-type">
          <option value="text"<% if (type === 'text') { %> selected<% } %>><?php _e( 'Text', 'wm_forms' ); ?></option>
          <option value="checkbox"<% if (type === 'checkbox') { %> selected<% } %>><?php _e( 'Checkbox', 'wm_forms' ); ?></option>
          <option value="radio"<% if (type === 'radio') { %> selected<% } %>><?php _e( 'Radio', 'wm_forms' ); ?></option>
          <option value="select"<% if (type === 'select') { %> selected<% } %>><?php _e( 'Select', 'wm_forms' ); ?></option>
          <option value="textarea"<% if (type === 'textarea') { %> selected<% } %>><?php _e( 'Textarea', 'wm_forms' ); ?></option>
          <option value="email"<% if (type === 'email') { %> selected<% } %>><?php _e( 'Email', 'wm_forms' ); ?></option>
        </select>
      </label>
    </p>
    <fieldset class="wm-form-options">
      <legend><?php _e( 'Options', 'wm_forms' ); ?></legend>
      <ol></ol>
      <button class="button button-small right wm-form-add-option"><?php _e( 'Add Option', 'wm_forms' ); ?></button>
    </fieldset>
    <input type="hidden" name="fid" class="wm-form-field-id" value="<%= fid %>">
    <a class="item-delete" href="#"><?php _e( 'Remove', 'wm_forms' ); ?></a>
  </dd>
</dl>
