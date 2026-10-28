@once
<svg class="school-glass-defs" xmlns="http://www.w3.org/2000/svg" width="0" height="0" aria-hidden="true" focusable="false">
 <defs>
  <filter id="ra-school-dashboard-refraction" color-interpolation-filters="sRGB" x="0%" y="0%" width="100%" height="100%">
   <feImage x="0" y="0" width="100%" height="100%" preserveAspectRatio="none" result="map" href="{{ asset('images/glass/dashboard-displacement.png') }}"/>
   <feDisplacementMap in="SourceGraphic" in2="map" result="dispRed" scale="-20" xChannelSelector="R" yChannelSelector="G"/>
   <feColorMatrix in="dispRed" type="matrix" values="1 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 1 0" result="red"/>
   <feDisplacementMap in="SourceGraphic" in2="map" result="dispGreen" scale="-24" xChannelSelector="R" yChannelSelector="G"/>
   <feColorMatrix in="dispGreen" type="matrix" values="0 0 0 0 0 0 1 0 0 0 0 0 0 0 0 0 0 0 1 0" result="green"/>
   <feDisplacementMap in="SourceGraphic" in2="map" result="dispBlue" scale="-28" xChannelSelector="R" yChannelSelector="G"/>
   <feColorMatrix in="dispBlue" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 1 0 0 0 0 0 1 0" result="blue"/>
   <feBlend in="red" in2="green" mode="screen" result="rg"/><feBlend in="rg" in2="blue" mode="screen" result="output"/>
   <feGaussianBlur in="output" stdDeviation="3"/>
  </filter>
 </defs>
</svg>
@endonce
