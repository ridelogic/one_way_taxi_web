# Step 1 — READ-ONLY comparison for Group A. Writes nothing, prints no secrets (no .env contents).
cd /home/u582318487/oneway-app || exit 1
echo "== A. Group A: live vs local (OK = already identical, FAILED = differs, missing = new file)"
sha256sum -c - 2>&1 <<'SUMS' | sed 's/^/  /'
b655f27afab8b7c04254ac0f565d0cfb04f8e4c7e57caed1bcf2524aa4da22fd  app/Http/Controllers/Admin/CityController.php
598e731d1e05c45cc4fc17ea1f0e378b788069c936c68654a7cfa902572de930  app/Http/Controllers/Admin/ContactMessageController.php
6d905fdf70580c70b1aa46875e8a693d888f76616662a77a3a6d38dc2c51601c  app/Http/Controllers/Admin/CorporateEnquiryController.php
534f699a6d6653f2bb78cbbc93c57ade69726a052600c9f380f6f7a42665b954  app/Http/Controllers/Admin/DashboardController.php
9e6cbbb51e5ceda8fb081e37c1ed252e9019c19b4db449204e3444e28f3f975a  app/Http/Controllers/Admin/HeroSlideController.php
37dd2a6afd6e1613824e00c626f376595d23189b33a069da3da9f9e970214432  app/Http/Controllers/Admin/MediaController.php
4839068babba56de0b249f27292705f6af016d27b2724dd37e8cb45fc8e979e6  app/Http/Controllers/Admin/NavigationController.php
04bb192c37f51d971f264f7d3e33da67f2b3f8c510da986eeef4e5aa3321a0d2  app/Http/Controllers/Admin/PageController.php
de41bc8859d1b12c3a82d9cf2089a8d3accab0f7509176a922d6d94e6b17e19c  app/Http/Controllers/Admin/PartnerEnquiryController.php
07662aaab1f4228a9b91cfed874de0fe19cae2bc00212abd19d5ea68f0a9286c  app/Http/Controllers/Admin/TaxiRouteController.php
c13f75cf3d35f61d3774ec51840fae8f2a41a2a6ee826ea0a7aee86f0683361a  app/Http/Controllers/Controller.php
c34242a91741cd0139d95ed3bbf6af15bce0834a7c57d233a9c769b357439057  app/Http/Middleware/AdminSecurityHeaders.php
26f3f483c6bb6da13691588c6975e3574e7be8ffb04342a0ac5f3bc65c6b50e8  app/Http/Requests/Admin/AdminRequest.php
b3c88bf9ce9b788a136fa174b332b91bfa3b40c5ef62e2e9a2356c001aae8908  app/Http/Requests/Admin/CityRequest.php
76bc858e8c1d4ad692cbd39c7e95e8d8d0f9f57f1b479490813e2791d4fd225b  app/Http/Requests/Admin/FareRuleRequest.php
750ff7e7c2c06798e168d901373ba604434ba4fce06a7d95bf5df219c83118fb  app/Http/Requests/Admin/HomeSectionRequest.php
4d827c07ff7f397e872aff00520e6fd975ca6261b0d9ee5b1d0a9f338b63ccaf  app/Http/Requests/Admin/MediaUploadRequest.php
0c589997887c6a74a13cfa9cccc59df2903cca9203a65790cad078b8fe003701  app/Http/Requests/Admin/PageRequest.php
3a44608e53598d2f911f2d9aa908d761b2446c9f376492649cac3115025be5d9  app/Http/Requests/Admin/ServiceRequest.php
9cce144ec01291bce0397ef034acf730405d931729223c2e361b3b9997a20f44  app/Http/Requests/Admin/SettingsRequest.php
630e94ddd6815fc9e60eeaefe113ddebe152cdce67725e17578b7583c940b044  app/Http/Requests/Admin/TaxiRouteRequest.php
e1647470b69374eae1b7a92f1a17da6fa1fa3eee1a5352c34ac6eaea6fdae55d  app/Http/Requests/Admin/VehicleRequest.php
61e69e831f999a096f73cc82aae4f0a1967c9b71bb0a490460610c50981d9c43  bootstrap/app.php
e44e68bdea6dd63eed889b5d5344780b52d02ac54324c2548360bb958279ea41  config/icons.php
7e6277f25b07950bde58051c158ade66f93b66790e56d96deddd5153b0d88af8  public/css/admin.css
fa70ba125b83a6210a2a014a2e43a49763d801859599645026a6bd8e1b9cc31f  public/images/demo/admin-login-scene.webp
288df0116866b7f39a64c88f624a0e0a681294c6afc4225aeaef11e22ae0a558  public/js/admin.js
0b89e02166ea6dff217e12b8784bec33d2351afeb7f88aa2a9be5f3a381e8696  resources/views/admin/auth/login.blade.php
165745fbaf2bcd1d52889fac4d812de447892f302966ae7010c1e56a54b3f9ee  resources/views/admin/bookings/show.blade.php
fba5549a8e0f8eee00a431307351163a3137c81ab72f0bb8d351cbf522f94fe6  resources/views/admin/cities/index.blade.php
2962ce0da990a01c0f655cbc1b86798679b6cdd01aec8321f2fd9b83a178a305  resources/views/admin/dashboard.blade.php
85d084d8b1e8e14e02ef55c026a689b1793583b9cf9fb3e5c1837e2c109d4bf6  resources/views/admin/enquiries/index.blade.php
dcf74d5ba10673ca4ff985eb1107438fa6e50c3734df9fa0e7cc1571e9974db2  resources/views/admin/homepage/index.blade.php
f71a00a7e22c9b39410318d55c51e1be377b808552cc99ca23b7f9de0d616fe8  resources/views/admin/layout.blade.php
d191994506586e3625702ba911efc6c43c20c686f360a65805bf46cdf3fbfaff  resources/views/admin/media/index.blade.php
cc476b0a36b46c4b66c6e0d89ba337aabb413ab510f52ff6e3cd4b2075c426e5  resources/views/admin/messages/index.blade.php
5727b2fcb2e7b22c2b587901b30b7be7a5a8d4d94388ff6a826ce1d662a22c48  resources/views/admin/routes/form.blade.php
5839d8bba4a3cdc86457a82e69134963eba3fa762153e088a9a8871649e71137  resources/views/admin/routes/index.blade.php
465e65f8103e9613641a9ef2b50aa416db95f4c1cd48882917ec0573b651c26a  routes/web.php
SUMS
echo "== B. Live details for Group A files (live sha256 prefix, size, mtime)"
while read -r h f; do
  if [ -e "$f" ]; then printf '  %s %8s %s  %s\n' "$(sha256sum "$f" | cut -c1-16)" "$(stat -c %s "$f")" "$(date -r "$f" '+%F %R')" "$f"
  else printf '  %-16s %8s %-16s  %s\n' MISSING - - "$f"; fi
done <<'SUMS'
b655f27afab8b7c04254ac0f565d0cfb04f8e4c7e57caed1bcf2524aa4da22fd  app/Http/Controllers/Admin/CityController.php
598e731d1e05c45cc4fc17ea1f0e378b788069c936c68654a7cfa902572de930  app/Http/Controllers/Admin/ContactMessageController.php
6d905fdf70580c70b1aa46875e8a693d888f76616662a77a3a6d38dc2c51601c  app/Http/Controllers/Admin/CorporateEnquiryController.php
534f699a6d6653f2bb78cbbc93c57ade69726a052600c9f380f6f7a42665b954  app/Http/Controllers/Admin/DashboardController.php
9e6cbbb51e5ceda8fb081e37c1ed252e9019c19b4db449204e3444e28f3f975a  app/Http/Controllers/Admin/HeroSlideController.php
37dd2a6afd6e1613824e00c626f376595d23189b33a069da3da9f9e970214432  app/Http/Controllers/Admin/MediaController.php
4839068babba56de0b249f27292705f6af016d27b2724dd37e8cb45fc8e979e6  app/Http/Controllers/Admin/NavigationController.php
04bb192c37f51d971f264f7d3e33da67f2b3f8c510da986eeef4e5aa3321a0d2  app/Http/Controllers/Admin/PageController.php
de41bc8859d1b12c3a82d9cf2089a8d3accab0f7509176a922d6d94e6b17e19c  app/Http/Controllers/Admin/PartnerEnquiryController.php
07662aaab1f4228a9b91cfed874de0fe19cae2bc00212abd19d5ea68f0a9286c  app/Http/Controllers/Admin/TaxiRouteController.php
c13f75cf3d35f61d3774ec51840fae8f2a41a2a6ee826ea0a7aee86f0683361a  app/Http/Controllers/Controller.php
c34242a91741cd0139d95ed3bbf6af15bce0834a7c57d233a9c769b357439057  app/Http/Middleware/AdminSecurityHeaders.php
26f3f483c6bb6da13691588c6975e3574e7be8ffb04342a0ac5f3bc65c6b50e8  app/Http/Requests/Admin/AdminRequest.php
b3c88bf9ce9b788a136fa174b332b91bfa3b40c5ef62e2e9a2356c001aae8908  app/Http/Requests/Admin/CityRequest.php
76bc858e8c1d4ad692cbd39c7e95e8d8d0f9f57f1b479490813e2791d4fd225b  app/Http/Requests/Admin/FareRuleRequest.php
750ff7e7c2c06798e168d901373ba604434ba4fce06a7d95bf5df219c83118fb  app/Http/Requests/Admin/HomeSectionRequest.php
4d827c07ff7f397e872aff00520e6fd975ca6261b0d9ee5b1d0a9f338b63ccaf  app/Http/Requests/Admin/MediaUploadRequest.php
0c589997887c6a74a13cfa9cccc59df2903cca9203a65790cad078b8fe003701  app/Http/Requests/Admin/PageRequest.php
3a44608e53598d2f911f2d9aa908d761b2446c9f376492649cac3115025be5d9  app/Http/Requests/Admin/ServiceRequest.php
9cce144ec01291bce0397ef034acf730405d931729223c2e361b3b9997a20f44  app/Http/Requests/Admin/SettingsRequest.php
630e94ddd6815fc9e60eeaefe113ddebe152cdce67725e17578b7583c940b044  app/Http/Requests/Admin/TaxiRouteRequest.php
e1647470b69374eae1b7a92f1a17da6fa1fa3eee1a5352c34ac6eaea6fdae55d  app/Http/Requests/Admin/VehicleRequest.php
61e69e831f999a096f73cc82aae4f0a1967c9b71bb0a490460610c50981d9c43  bootstrap/app.php
e44e68bdea6dd63eed889b5d5344780b52d02ac54324c2548360bb958279ea41  config/icons.php
7e6277f25b07950bde58051c158ade66f93b66790e56d96deddd5153b0d88af8  public/css/admin.css
fa70ba125b83a6210a2a014a2e43a49763d801859599645026a6bd8e1b9cc31f  public/images/demo/admin-login-scene.webp
288df0116866b7f39a64c88f624a0e0a681294c6afc4225aeaef11e22ae0a558  public/js/admin.js
0b89e02166ea6dff217e12b8784bec33d2351afeb7f88aa2a9be5f3a381e8696  resources/views/admin/auth/login.blade.php
165745fbaf2bcd1d52889fac4d812de447892f302966ae7010c1e56a54b3f9ee  resources/views/admin/bookings/show.blade.php
fba5549a8e0f8eee00a431307351163a3137c81ab72f0bb8d351cbf522f94fe6  resources/views/admin/cities/index.blade.php
2962ce0da990a01c0f655cbc1b86798679b6cdd01aec8321f2fd9b83a178a305  resources/views/admin/dashboard.blade.php
85d084d8b1e8e14e02ef55c026a689b1793583b9cf9fb3e5c1837e2c109d4bf6  resources/views/admin/enquiries/index.blade.php
dcf74d5ba10673ca4ff985eb1107438fa6e50c3734df9fa0e7cc1571e9974db2  resources/views/admin/homepage/index.blade.php
f71a00a7e22c9b39410318d55c51e1be377b808552cc99ca23b7f9de0d616fe8  resources/views/admin/layout.blade.php
d191994506586e3625702ba911efc6c43c20c686f360a65805bf46cdf3fbfaff  resources/views/admin/media/index.blade.php
cc476b0a36b46c4b66c6e0d89ba337aabb413ab510f52ff6e3cd4b2075c426e5  resources/views/admin/messages/index.blade.php
5727b2fcb2e7b22c2b587901b30b7be7a5a8d4d94388ff6a826ce1d662a22c48  resources/views/admin/routes/form.blade.php
5839d8bba4a3cdc86457a82e69134963eba3fa762153e088a9a8871649e71137  resources/views/admin/routes/index.blade.php
465e65f8103e9613641a9ef2b50aa416db95f4c1cd48882917ec0573b651c26a  routes/web.php
SUMS
echo "== C. Live-only changes: app files modified on the server after the 2026-10-18 release (names + dates only)"
find app bootstrap config routes resources public database composer.json composer.lock \
  -path bootstrap/cache -prune -o -path public/storage -prune -o \
  -type f -newermt '2026-10-18 09:00' -printf '  %TY-%Tm-%Td %TH:%TM  %p\n' | sort
echo "== D. Context"
php -v | head -1; ls bootstrap/cache/; php artisan migrate:status 2>&1 | tail -3
