<div class="settings">
    <a href="#" class="btn btn-floating btn-icon btn-primary" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSettings" aria-controls="offcanvasSettings" aria-label="Theme Settings">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
            <path d="M3 21v-4a4 4 0 1 1 4 4h-4" />
            <path d="M21 3a16 16 0 0 0 -12.8 10.2" />
            <path d="M21 3a16 16 0 0 1 -10.2 12.8" />
            <path d="M10.6 9a9 9 0 0 1 4.4 4.4" />
        </svg>
    </a>
    <form class="offcanvas offcanvas-start offcanvas-narrow" tabindex="-1" id="offcanvasSettings">
        <div class="offcanvas-header">
            <h2 class="offcanvas-title">Theme Settings</h2>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
            <div>
                <div class="mb-4">
                    <label class="form-label">Color mode</label>
                    <p class="form-hint">Choose the color mode for your app.</p>
                    <div class="form-selectgroup">
                        <label class="form-selectgroup-item">
                            <input type="radio" name="theme-mode" value="light" class="form-selectgroup-input" checked />
                            <span class="form-selectgroup-label">Light</span>
                        </label>
                        <label class="form-selectgroup-item">
                            <input type="radio" name="theme-mode" value="dark" class="form-selectgroup-input" />
                            <span class="form-selectgroup-label">Dark</span>
                        </label>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Color scheme</label>
                    <p class="form-hint">The perfect color mode for your app.</p>
                    <div class="row g-2">
                        <div class="col-auto">
                            <label class="form-colorinput">
                                <input name="theme-primary" type="radio" value="blue" class="form-colorinput-input" checked />
                                <span class="form-colorinput-color bg-blue"></span>
                            </label>
                        </div>
                        </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Font family</label>
                    <p class="form-hint">Choose the font family that fits your app.</p>
                    <div class="form-selectgroup">
                        <label class="form-selectgroup-item">
                            <input type="radio" name="theme-font" value="sans-serif" class="form-selectgroup-input" checked />
                            <span class="form-selectgroup-label">Sans-serif</span>
                        </label>
                        </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Theme base</label>
                    <p class="form-hint">Choose the gray shade for your app.</p>
                    <div class="form-selectgroup">
                        <label class="form-selectgroup-item">
                            <input type="radio" name="theme-base" value="gray" class="form-selectgroup-input" checked />
                            <span class="form-selectgroup-label">Gray</span>
                        </label>
                        </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Corner Radius</label>
                    <p class="form-hint">Choose the border radius factor for your app.</p>
                    <div class="form-selectgroup">
                        <label class="form-selectgroup-item">
                            <input type="radio" name="theme-radius" value="0" class="form-selectgroup-input" />
                            <span class="form-selectgroup-label">0</span>
                        </label>
                        </div>
                </div>
            </div>
            <div class="mt-auto space-y">
                <button type="button" class="btn w-100" id="reset-changes">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M19.95 11a8 8 0 1 0 -.5 4m.5 5v-5h-5" />
                    </svg>
                    Reset changes
                </button>
                <a href="#" class="btn btn-primary w-100" data-bs-dismiss="offcanvas"> Save </a>
            </div>
        </div>
    </form>
</div>