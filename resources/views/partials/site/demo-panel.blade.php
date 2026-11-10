{{-- Prototype presentation controls (localStorage simulation). Hidden unless config('site.demo_panel'). --}}
    <div class="d-demo-fab">
      <button type="button"><i class="fas fa-flask"></i> Demo controls</button>
      <div class="d-demo-panel">
        <h6>Presentation controls</h6>
        <p>Prototype only — all data is simulated in this browser. Use these to show failure scenarios.</p>
        <label><span>Insurer API timeout (ADNIC)</span><span class="d-switch"><input type="checkbox" data-demo-setting="simulateProviderTimeout" /><i></i></span></label>
        <label><span>Payment declined</span><span class="d-switch"><input type="checkbox" data-demo-setting="simulatePaymentFailure" /><i></i></span></label>
        <label><span>ERP sync failure</span><span class="d-switch"><input type="checkbox" data-demo-setting="simulateErpFailure" /><i></i></span></label>
        <div class="links">
          <a href="{{ url(config('site.links.demo')) }}"><i class="fal fa-route"></i> Demo guide</a>
          <a href="{{ url(config('site.links.admin')) }}"><i class="fal fa-gauge-high"></i> Admin</a>
          <a href="{{ url(config('site.links.portal')) }}"><i class="fal fa-user"></i> Customer portal</a>
          <button type="button" data-demo-reset><i class="fal fa-rotate"></i> Reset data</button>
        </div>
      </div>
    </div>
