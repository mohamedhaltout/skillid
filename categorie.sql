-- 🏗️ Construction
INSERT INTO Categories (nom, description, icone, type) VALUES
('General Masonry', 'Structural masonry and foundational work', 'construction.svg', 'standard'),
('Tiling & Flooring', 'Tile installation and floor finishing', 'construction.svg', 'standard'),
('Thermal/Acoustic Insulation', 'Home insulation for sound and temperature', 'construction.svg', 'standard'),
('Light Demolition', 'Wall removal and small structure demolitions', 'construction.svg', 'standard'),

-- 🪚 Carpentry
('Interior Carpentry', 'Woodworks for interiors', 'carpentry.svg', 'standard'),
('Door & Window Installation', 'Fitting wooden or PVC doors and windows', 'carpentry.svg', 'standard'),
('Custom Furniture', 'Made-to-measure furniture building', 'carpentry.svg', 'standard'),

-- ⚡ Electricity
('General Electrical Work', 'Household or office wiring and repair', 'electricity.svg', 'standard'),
('Lighting Installation', 'LED, ceiling lights, and fixtures', 'electricity.svg', 'standard'),
('TV / Satellite Setup', 'TV mounting and satellite dish setup', 'electricity.svg', 'standard'),

-- 🚰 Plumbing
('General Plumbing', 'Water lines, leaks, and repairs', 'plumbing.svg', 'standard'),
('Sanitary Installation', 'Toilets, sinks, and bathroom fittings', 'plumbing.svg', 'standard'),
('Unclogging & Leak Repair', 'Drain unblocking and pipe repair', 'plumbing.svg', 'standard'),

-- 🎨 Painting
('Interior Painting', 'Wall and ceiling painting', 'painting.svg', 'standard'),
('Humidity Treatment', 'Mold & damp-proof coating', 'painting.svg', 'standard'),
('Wallpaper & Decorative Coverings', 'Decorative wall finish installation', 'painting.svg', 'standard'),

-- ❄️ Air Conditioning / Ventilation
('Air Conditioning & Ventilation', 'AC units and ventilation systems', 'ac.svg', 'standard'),

-- 🧽 Cleaning
('Post-Construction Cleaning', 'Deep cleaning after renovation', 'cleaning.svg', 'standard'),

-- 🔩 Metalwork
('Welding & Metal Fabrication', 'Doors, windows, custom metal structures', 'metalwork.svg', 'standard'),

-- 🪟 Aluminum
('Aluminum Window & Door Installation', 'Glass and aluminum frame fitting', 'aluminum.svg', 'standard'),

-- 🌿 Gardening
('Landscaping & Planting', 'Garden setup, trees and plants', 'gardening.svg', 'standard'),
('Synthetic Grass Installation', 'Artificial lawn installation', 'gardening.svg', 'standard'),

-- 🛡️ Security
('Alarms & Surveillance Cameras', 'Security system setup', 'security.svg', 'standard'),

-- 🧰 Handyman
('Repairs & Multiservices', 'General repairs and maintenance', 'handyman.svg', 'standard');

-- 🚨 Emergency Categories
INSERT INTO Categories (nom, description, icone, type) VALUES
('Water Leak', 'Burst pipes, leaking taps or toilets', 'water_leakage.svg', 'emergency'),
('Power Outage', 'Sudden blackouts or electrical shutdown', 'power_outage.svg', 'emergency'),
('Gas Leak Detection', 'Suspicious gas smell, urgent leak', 'gas_leak.svg', 'emergency'),
('Broken Lock / Blocked Door', 'Key stuck or broken, can’t open the door', 'broken_lock.svg', 'emergency'),
('Toilet Overflow', 'Clogged or overflowing toilet', 'toilet_overflow.svg', 'emergency'),
('Roof Leak', 'Water dripping from ceiling or roof', 'roof_leak.svg', 'emergency'),
('Electrical Short Circuit', 'Sparks or burning smell from outlets', 'short_circuit.svg', 'emergency'),
('Appliance Repair', 'Fridge, washing machine, water heater', 'appliance_repair.svg', 'emergency'),
('Pest Infestation', 'Urgent insect or rodent control', 'pest_infestation.svg', 'emergency'),
('Emergency Cleaning', 'Flood, fire, or disaster cleanup', 'emergency_cleaning.svg', 'emergency');
