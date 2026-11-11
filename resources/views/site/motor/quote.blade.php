@extends('layouts.site', ['active' => 'motorquote'])

@section('title', 'Motor Insurance Quote — Delta Insurance')
@section('description', 'Get and compare motor insurance quotes online.')

@section('content')


        <section class="d-flow">
          <div class="container-x">
            <div class="d-flow__head">
              <div><span class="eyebrow">Motor Insurance · Online quote</span><h1 class="d-h2">Get your motor insurance quote</h1></div>
              <span class="d-badge d-badge--advisor"><i class="fal fa-clock"></i> About 5 minutes · Emirates ID, licence &amp; Mulkiya needed</span>
            </div>
            <ul class="d-stepper" style="padding:0"><li class="is-active" data-step-ind="1"><span class="n"><span>1</span></span>About you</li><li class="" data-step-ind="2"><span class="n"><span>2</span></span>Driving licence</li><li class="" data-step-ind="3"><span class="n"><span>3</span></span>Vehicle</li><li class="" data-step-ind="4"><span class="n"><span>4</span></span>Documents & cover</li><li class="" data-step-ind="5"><span class="n"><span>5</span></span>Review</li></ul>
            <div class="row g-4">
              <div class="col-xl-8">
                <form id="quoteForm" class="d-panel" novalidate method="post" action="{{ route('motor.quote.store') }}" enctype="multipart/form-data">
                  @csrf
                  <!-- STEP 1 -->
                  <div class="d-step-pane is-active" data-step="1">
                    <div class="d-panel__title"><i class="fal fa-user"></i><div><h2>About you</h2><p>The policyholder, exactly as on the Emirates ID.</p></div></div>
                    <div class="row g-3">
                      <div class="col-md-6"><label class="d-field"><span>Full name (English) <em>*</em></span><input class="d-input" name="name" required autocomplete="name" /><small class="d-error">Please enter your full name.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Full name (Arabic)</span><input class="d-input" name="nameAr" dir="rtl" placeholder="الاسم كما في الهوية" /><small class="d-hint">Optional — as printed on the Emirates ID.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required autocomplete="email" /><small class="d-error">Please enter a valid email.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required inputmode="tel" placeholder="50 123 4567" /></div><small class="d-error">Please enter a UAE mobile number.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Date of birth <em>*</em></span><input class="d-input" type="date" name="dob" required /><small class="d-error">Please enter your date of birth.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Gender <em>*</em></span><select class="d-select" name="gender" required></select><small class="d-error">Please select.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Nationality <em>*</em></span><select class="d-select" name="nationality" required></select><small class="d-error">Please select.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Emirate of residence <em>*</em></span><select class="d-select" name="emirate" required></select><small class="d-error">Please select.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Emirates ID number <em>*</em></span><input class="d-input" name="emiratesId" required inputmode="numeric" placeholder="784-XXXX-XXXXXXX-X" /><small class="d-error">Format: 784-XXXX-XXXXXXX-X</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Emirates ID expiry <em>*</em></span><input class="d-input" type="date" name="eidExpiry" required /><small class="d-error">Please enter the expiry date.</small></label></div>
                    </div>
                    <div class="d-elig" data-elig="1"></div>
                  </div>
                  <!-- STEP 2 -->
                  <div class="d-step-pane" data-step="2">
                    <div class="d-panel__title"><i class="fal fa-id-card"></i><div><h2>Driving licence</h2><p>UAE driving licence of the policyholder.</p></div></div>
                    <div class="row g-3">
                      <div class="col-md-6"><label class="d-field"><span>Licence number <em>*</em></span><input class="d-input" name="licenseNo" required inputmode="numeric" /><small class="d-error">Please enter the licence number.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Years without a claim <em>*</em></span><select class="d-select" name="ncdYears" required></select><small class="d-hint">You may be asked for a no-claims certificate.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>First issue date <em>*</em></span><input class="d-input" type="date" name="licenseFrom" required /><small class="d-error">Please enter the issue date.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Expiry date <em>*</em></span><input class="d-input" type="date" name="licenseTo" required /><small class="d-error">Please enter the expiry date.</small></label></div>
                    </div>
                    <div class="d-elig" data-elig="2"></div>
                  </div>
                  <!-- STEP 3 -->
                  <div class="d-step-pane" data-step="3">
                    <div class="d-panel__title"><i class="fal fa-car"></i><div><h2>Your vehicle</h2><p>Details from the vehicle registration card (Mulkiya).</p></div></div>
                    <div class="row g-3">
                      <div class="col-md-6"><label class="d-field"><span>What are you doing? <em>*</em></span><select class="d-select" name="trafficTranType" required></select><small class="d-error">Please select.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Is the vehicle brand new?</span><select class="d-select" name="isNew"><option value="no">No — already registered</option><option value="yes">Yes — brand new</option></select></label></div>
                      <div class="col-md-8"><label class="d-field"><span>Chassis number (VIN) <em>*</em></span><div class="d-vin"><input class="d-input" name="chassis" required maxlength="17" placeholder="17 characters" style="text-transform:uppercase" /><button type="button" class="d-btn d-btn--ghost d-btn--sm" id="vinBtn"><i class="fal fa-magnifying-glass"></i> Find vehicle</button></div><small class="d-error">VIN must be 17 characters.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Traffic file number (TCF) <em>*</em></span><input class="d-input" name="tcfNumber" required inputmode="numeric" /><small class="d-error">Required.</small></label></div>
                    </div>
                    <div class="d-vehicle" id="vehBox">
                      <div class="row g-3">
                        <div class="col-md-4"><label class="d-field"><span>Make <em>*</em></span><select class="d-select" name="make" required></select><small class="d-error">Please select.</small></label></div>
                        <div class="col-md-4"><label class="d-field"><span>Model <em>*</em></span><select class="d-select" name="model" required><option value="">Select make first</option></select><small class="d-error">Please select.</small></label></div>
                        <div class="col-md-4"><label class="d-field"><span>Model year <em>*</em></span><select class="d-select" name="year" required></select><small class="d-error">Please select.</small></label></div>
                        <div class="col-md-6"><label class="d-field"><span>GCC specification?</span><select class="d-select" name="gccSpec"><option value="yes">Yes</option><option value="no">No (imported)</option></select></label></div>
                        <div class="col-md-6"><label class="d-field"><span>Electric vehicle?</span><select class="d-select" name="ev"><option value="no">No</option><option value="yes">Yes</option></select></label></div>
                      </div>
                      <small class="d-hint d-block mt-10" id="vehHint"><i class="fal fa-circle-info"></i> In the live system these details and the insured value come from the insurer (EDATA) using the chassis number.</small>
                      @include('partials.motor.old-car-note')
                    </div>
                    <div class="d-subhead">Registration &amp; plate</div>
                    <div class="row g-3">
                      <div class="col-md-4"><label class="d-field"><span>Plate source <em>*</em></span><select class="d-select" name="plateSource" required></select><small class="d-error">Please select.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Plate code <em>*</em></span><select class="d-select" name="plateCode" required></select><small class="d-error">Please select.</small></label></div>
                      <div class="col-md-4"><label class="d-field"><span>Plate number <em>*</em></span><input class="d-input" name="regNumber" required inputmode="numeric" /><small class="d-error">Required.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Registration date <em>*</em></span><input class="d-input" type="date" name="regDt" required /><small class="d-error">Required.</small></label></div>
                      <div class="col-md-6"><label class="d-field"><span>Is the vehicle financed?</span><select class="d-select" name="financed"><option value="no">No</option><option value="yes">Yes — bank / finance company</option></select></label></div>
                      <div class="col-md-12" id="bankWrap" style="display:none"><label class="d-field"><span>Financing bank <em>*</em></span><select class="d-select" name="bank"></select><small class="d-error">Please select the bank.</small></label></div>
                    </div>
                    <div class="d-elig" data-elig="3"></div>
                  </div>
                  <!-- STEP 4 -->
                  <div class="d-step-pane" data-step="4">
                    <div class="d-panel__title"><i class="fal fa-file-arrow-up"></i><div><h2>Documents</h2><p>The insurer checks these before pricing. PDF, JPG or PNG · max 10 MB each.</p></div>@if (config('site.demo'))<button type="button" class="d-btn d-btn--ghost d-btn--sm ms-auto" id="allSamples">Use sample files</button>@endif</div>
                    <div class="row g-3" id="uploads"></div>
                    <small class="d-error" id="docErr">Please upload all required documents.</small>
                    <div class="d-subhead">Cover</div>
                    <div class="row g-3">
                      <div class="col-md-6"><label class="d-opt"><input type="radio" name="coverType" value="comprehensive" checked /><span class="d-opt__box"><i class="fal fa-shield-check ico"></i><span><b>Comprehensive</b><small>Own damage, theft, fire + third-party liability.</small></span><span class="tick"><i class="fas fa-check"></i></span></span></label></div>
                      <div class="col-md-6"><label class="d-opt"><input type="radio" name="coverType" value="third_party" /><span class="d-opt__box"><i class="fal fa-car-burst ico"></i><span><b>Third Party Liability</b><small>Damage or injury to others. Minimum legal cover.</small></span><span class="tick"><i class="fas fa-check"></i></span></span></label></div>
                    </div>
                    @include('partials.motor.old-car-note')
                    <div class="d-elig" data-elig="4"></div>
                    <div id="repairWrap">
                      <div class="d-subhead">Repair preference</div>
                      <div class="d-seg"><label><input type="radio" name="repair" value="any" checked /><span>Show all</span></label><label><input type="radio" name="repair" value="agency" /><span>Agency repair</span></label><label><input type="radio" name="repair" value="garage" /><span>Garage repair</span></label></div>
                      <small class="d-hint">Agency repair is typically available for vehicles up to 5 years old.</small>
                    </div>
                    <div class="d-subhead">Optional add-ons</div>
                    <div class="row g-3">
                      <div class="col-md-6"><label class="d-opt"><input type="checkbox" name="roadside" checked /><span class="d-opt__box"><i class="fal fa-truck-pickup ico"></i><span><b>Roadside assistance</b><small>Towing, battery boost, flat tyre.</small></span><span class="tick"><i class="fas fa-check"></i></span></span></label></div>
                      <div class="col-md-6"><label class="d-opt"><input type="checkbox" name="rentACar" /><span class="d-opt__box"><i class="fal fa-car-building ico"></i><span><b>Rent-a-car</b><small>Replacement car while yours is repaired.</small></span><span class="tick"><i class="fas fa-check"></i></span></span></label></div>
                      <div class="col-md-6"><label class="d-opt"><input type="checkbox" name="pa" /><span class="d-opt__box"><i class="fal fa-user-shield ico"></i><span><b>Personal accident</b><small>Driver and passengers.</small></span><span class="tick"><i class="fas fa-check"></i></span></span></label></div>
                      <div class="col-md-6"><label class="d-opt"><input type="checkbox" name="gcc" /><span class="d-opt__box"><i class="fal fa-earth-asia ico"></i><span><b>GCC cover</b><small>Drive in GCC countries.</small></span><span class="tick"><i class="fas fa-check"></i></span></span></label></div>
                    </div>
                    <div class="row g-3 mt-10">
                      <div class="col-md-6"><label class="d-field"><span>Policy start date <em>*</em></span><input class="d-input" type="date" name="policyStart" required /><small class="d-error">Choose a date within the next 30 days.</small></label></div>
                    </div>
                  </div>
                  <!-- STEP 5 -->
                  <div class="d-step-pane" data-step="5">
                    <div class="d-panel__title"><i class="fal fa-list-check"></i><div><h2>Review &amp; submit</h2><p>Check your details before we contact the insurers.</p></div></div>
                    <div class="d-review" id="reviewBox"></div>
                    <details class="d-tech mt-15"><summary><i class="fal fa-code"></i> Technical: Dubai Insurance GenerateQuote request</summary><pre id="dinPreview"></pre></details>
                    <div class="d-subhead">Consent</div>
                    <label class="d-check mb-10"><input type="checkbox" name="consentShare" required /> I authorise Delta Insurance Services to share my information and documents with its partner insurers to obtain motor insurance quotations.</label>
                    <label class="d-check"><input type="checkbox" name="consentTerms" required /> I confirm the information provided is accurate and I accept the <a class="d-link" href="#!">Terms</a> and <a class="d-link" href="#!">Privacy Policy</a>.</label>
                    <small class="d-error" id="consentErr">Please accept both statements to continue.</small>
                  </div>
                  <div class="d-actions">
                    <button type="button" class="d-btn d-btn--ghost" id="backBtn" style="visibility:hidden"><i class="far fa-arrow-left"></i> Back</button>
                    <button type="button" class="d-btn d-btn--primary" id="nextBtn">Continue <i class="far fa-arrow-right"></i></button>
                  </div>
                </form>
              </div>
              <div class="col-xl-4">
                <div class="d-aside">
                  <div class="d-card mb-3">
                    <h4><i class="fal fa-receipt me-2" style="color:var(--d-sky-300)"></i>Your quote summary</h4>
                    <div id="liveSummary"><p class="d-summary-empty">Your details will appear here as you go.</p></div>
                  </div>
                  <div class="d-card mb-3">
                    <h4>Insurers we will ask</h4>
                    <div id="provList" class="d-grid gap-2">@foreach ($insurers as $ins)<div class="d-flex align-items-center gap-2">@include('partials.motor.logo', ['ins' => $ins, 'size' => 'sm'])<span style="color:var(--ink);font-size:14px">{{ $ins['name'] }}</span><span class="ms-auto d-badge d-badge--online" style="font-size:11px">{{ app()->isProduction() ? 'Live API' : 'Live API (UAT)' }}</span></div>@endforeach</div>
                    <p class="d-muted-2 mt-10 mb-0" style="font-size:12.5px">Requests are sent in parallel. If one insurer does not respond, you still see the others.</p>
                  </div>
                  <div class="d-note"><i class="fal fa-lock"></i><div>Your information is encrypted and only shared with insurers to prepare your quotes.</div></div>
                </div>
              </div>
            </div>
          </div>
        </section>
        <div class="d-overlay" id="loadingOverlay" role="dialog" aria-live="polite" aria-label="Requesting quotes">
          <div class="d-overlay__box">
            <div class="d-spinner"></div>
            <h3>Requesting your quotes</h3>
            <p class="d-muted mb-0" id="loadingMsg">Contacting insurers in parallel…</p>
            <div class="d-prov-list" id="loadingList"></div>
          </div>
        </div>
@endsection

@push('scripts')
    @php($motor = ['demo' => (bool) config('site.demo'), 'insurers' => $insurers, 'contactUrl' => route('contact')])
    <script>window.MOTOR = @json($motor);</script>
    <script src="{{ asset('assets/js/din-codes.js') }}"></script>
    <script src="{{ asset('assets/js/pages/motor-quote.js') }}"></script>
@endpush
