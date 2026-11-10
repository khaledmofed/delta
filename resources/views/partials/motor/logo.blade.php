{{-- Insurer logo — same markup as DELTA.util.logo() in delta-store.js. $ins = MotorPresenter::insurer(); $size = '' | sm | lg --}}
<span class="ins-logo {{ ($size ?? '') ? 'ins-logo--'.$size : '' }}" title="{{ $ins['name'] }}"><img src="{{ $ins['logo'] }}" alt="{{ $ins['name'] }}" data-pid="{{ $ins['code'] }}" onerror="DELTA.util&&DELTA.util.logoFallback(this)" /></span>
