{{-- "Trusted by leading UAE insurers" — order and Live / Coming soon come from the insurers table
     (admin panel → Providers → Website logo strip). Insurers without a logo file show their name with a monogram. --}}
@php($stripInsurers = $stripInsurers ?? \App\Models\Insurer::forWebsite())
@if ($stripInsurers->isNotEmpty())
          <div class="insurers insurers--grid">
            <span>Trusted by leading UAE insurers</span>
            <div class="list">
@foreach ($stripInsurers as $ins)
              <span class="ins {{ $ins->isLive() ? 'is-live' : 'is-soon' }}">
@if ($src = $ins->logoUrl())
                <span class="ins-logo" title="{{ $ins->name }}"><img src="{{ $src }}" alt="{{ $ins->name }}" loading="lazy" /></span>
@else
                <span class="ins-logo d-mono is-fallback" aria-hidden="true">{{ $ins->initials() }}</span>
@endif
                <span class="tx"><span class="nm">{{ $ins->name }}</span>@if ($ins->isLive())<span class="ins-tag ins-tag--live">Live</span>@else<span class="ins-tag ins-tag--soon">Coming soon</span>@endif</span>
              </span>
@endforeach
            </div>
          </div>
@endif
