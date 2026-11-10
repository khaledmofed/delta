{{-- Hidden fields for the website lead forms (LeadController@store). The honeypot stays empty for people; bots fill it. --}}
@csrf
<input type="hidden" name="source" value="{{ $source }}" />
<div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden"><label>Company website <input type="text" name="company_website" tabindex="-1" autocomplete="off" /></label></div>
