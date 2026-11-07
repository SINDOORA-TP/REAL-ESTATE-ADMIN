/**
 * Magaram Real Estate - Universal Property & Settings Data Store
 * Provides persistent client-side data management for Coimbatore luxury properties.
 * Works seamlessly across GitHub Pages and PHP server environments.
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.PropertyStore = factory();
    }
}(typeof self !== 'undefined' ? self : this, function () {
    'use strict';

    const STORAGE_KEY = 'magaram_properties_coimbatore_v1';
    const ADMIN_AUTH_KEY = 'magaram_admin_auth_v1';
    const SETTINGS_KEY = 'magaram_site_settings_v1';
    const ENQUIRIES_KEY = 'magaram_enquiries_coimbatore_v1';

    // Default Inquiries
    const DEFAULT_ENQUIRIES = [
        {
            id: 1,
            name: 'Vikramaditya Rao',
            email: 'vikram@apex.in',
            phone: '+91 98430 43210',
            type: 'Site Visit',
            property_title: 'The Sovereign Penthouse, Race Course',
            message: 'Looking for duplex floor plan and private elevator specifications.',
            status: 'New',
            created_at: new Date(Date.now() - 3600000).toLocaleString()
        },
        {
            id: 2,
            name: 'Dr. Aisha Merchant',
            email: 'aisha@hospital.org',
            phone: '+91 98421 22334',
            type: 'Brochure',
            property_title: 'The Elysian Sanctuary Villa, Vadavalli',
            message: 'Requesting architectural elevations and landscaped plot dimensions.',
            status: 'Contacted',
            created_at: new Date(Date.now() - 86400000).toLocaleString()
        },
        {
            id: 3,
            name: 'Karan Singhania',
            email: 'karan@singhania.com',
            phone: '+91 99444 88888',
            type: 'Enquiry',
            property_title: 'Prestige Apex Commercial, Peelamedu',
            message: 'Inquiring regarding whole-floor acquisition for regional tech HQ.',
            status: 'Completed',
            created_at: new Date(Date.now() - 172800000).toLocaleString()
        }
    ];

    // Default Coimbatore Luxury Properties
    const DEFAULT_PROPERTIES = [
        {
            id: 1,
            title: 'The Sovereign Penthouse [Sample]',
            slug: 'the-sovereign-penthouse-coimbatore',
            category: 'Apartment',
            location: 'Race Course, Coimbatore',
            price: 85000000,
            price_display: '₹8.50 Cr',
            area_sqft: 5600,
            bedrooms: 4,
            bathrooms: 5,
            description: 'An ultra-exclusive double-height penthouse overlooking the lush green canopy of Race Course Road in Coimbatore. Features private biometric elevator access, an expansive open terrace with sky deck plunge pool, imported Calacatta marble, and 360-degree skyline vistas.',
            status: 'Available',
            is_featured: 1,
            is_sample: 1,
            cover_image: 'assets/img/property-1.svg',
            created_at: new Date().toISOString()
        },
        {
            id: 2,
            title: 'The Elysian Sanctuary Villa [Sample]',
            slug: 'the-elysian-sanctuary-villa-vadavalli',
            category: 'Villa',
            location: 'Vadavalli Foothills, Coimbatore',
            price: 125000000,
            price_display: '₹12.50 Cr',
            area_sqft: 8200,
            bedrooms: 5,
            bathrooms: 6,
            description: 'Set against the serene Western Ghats foothills in Vadavalli, this contemporary architectural estate features landscaped courtyards, natural stone facades, private temperature-controlled pool, home automation, and staff quarters.',
            status: 'Available',
            is_featured: 1,
            is_sample: 1,
            cover_image: 'assets/img/property-2.svg',
            created_at: new Date(Date.now() - 86400000).toISOString()
        },
        {
            id: 3,
            title: 'Prestige Apex Commercial Tower [Sample]',
            slug: 'prestige-apex-commercial-peelamedu',
            category: 'Commercial',
            location: 'Avinashi Road, Peelamedu, Coimbatore',
            price: 360000000,
            price_display: '₹36.00 Cr',
            area_sqft: 28000,
            bedrooms: 0,
            bathrooms: 10,
            description: 'Grade-A corporate commercial tower positioned along Coimbatore\'s prime Avinashi Road arterial corridor. LEED Gold certified, high-speed destination elevators, column-free floor plates, and executive boardroom suites.',
            status: 'Available',
            is_featured: 1,
            is_sample: 1,
            cover_image: 'assets/img/property-3.svg',
            created_at: new Date(Date.now() - 172800000).toISOString()
        },
        {
            id: 4,
            title: 'The Aurum Signature Suites [Sample]',
            slug: 'the-aurum-signature-suites-rs-puram',
            category: 'Apartment',
            location: 'R.S. Puram, Coimbatore',
            price: 68000000,
            price_display: '₹6.80 Cr',
            area_sqft: 4200,
            bedrooms: 4,
            bathrooms: 4,
            description: 'A boutique development of private single-floor residences in the heart of heritage R.S. Puram. Featuring soundproof acoustic glazing, private master suite with custom walk-in dressing chambers, and Italian modular fittings.',
            status: 'Available',
            is_featured: 1,
            is_sample: 1,
            cover_image: 'assets/img/property-4.svg',
            created_at: new Date(Date.now() - 259200000).toISOString()
        },
        {
            id: 5,
            title: 'Serene Palm Orchards Estate [Sample]',
            slug: 'serene-palm-orchards-thondamuthur',
            category: 'Villa',
            location: 'Siruvani Foothills, Coimbatore',
            price: 145000000,
            price_display: '₹14.50 Cr',
            area_sqft: 9500,
            bedrooms: 6,
            bathrooms: 7,
            description: 'Sprawling private gated farm estate with organic coconut orchards, Olympic-length private swimming pool, independent guest clubhouse pavilion, and 24/7 security perimeter.',
            status: 'Upcoming',
            is_featured: 1,
            is_sample: 1,
            cover_image: 'assets/img/property-5.svg',
            created_at: new Date(Date.now() - 345600000).toISOString()
        },
        {
            id: 6,
            title: 'The Opus Tech Pinnacle [Sample]',
            slug: 'the-opus-tech-pinnacle-saravanampatti',
            category: 'Commercial',
            location: 'Saravanampatti IT Corridor, Coimbatore',
            price: 240000000,
            price_display: '₹24.00 Cr',
            area_sqft: 22000,
            bedrooms: 0,
            bathrooms: 8,
            description: 'High-yield commercial tech headquarters designed for global SaaS and engineering enterprises in Coimbatore\'s premier IT hub.',
            status: 'Sold',
            is_featured: 0,
            is_sample: 1,
            cover_image: 'assets/img/property-6.svg',
            created_at: new Date(Date.now() - 432000000).toISOString()
        }
    ];

    const DEFAULT_SETTINGS = {
        site_name: 'Magaram Real Estate',
        tagline: 'Curating Extraordinary Living Spaces in Coimbatore',
        contact_phone: '+91 98430 12345',
        contact_email: 'contact@magaramrealestate.com',
        office_address: 'Magaram Towers, Level 5, Race Course Road, Coimbatore, Tamil Nadu - 641018',
        map_embed_url: 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3916.326292398491!2d76.9712165757467!3d11.01183348915159!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ba859ad33cfd2db%3A0x8aa50c60f4ff2b7!2sRace%20Course%2C%20Coimbatore%2C%20Tamil%20Nadu!5e0!3m2!1sen!2sin!4v1700000000000!5m2!1sen!2sin',
        show_stats: '1',
        stat_1_val: '15+',
        stat_1_lbl: 'Years of Heritage',
        stat_2_val: '75+',
        stat_2_lbl: 'Bespoke Projects',
        stat_3_val: '₹350Cr+',
        stat_3_lbl: 'Properties Transacted',
        stat_4_val: '99.2%',
        stat_4_lbl: 'Client Satisfaction'
    };

    const DEFAULT_AUTH = {
        username: 'admin',
        password: 'admin123',
        name: 'Magaram Senior Director',
        email: 'director@magaramrealestate.com'
    };

    function formatINR(val) {
        val = Number(val);
        if (val >= 10000000) {
            return '₹' + (val / 10000000).toFixed(2).replace(/\.00$/, '') + ' Cr';
        } else if (val >= 100000) {
            return '₹' + (val / 100000).toFixed(2).replace(/\.00$/, '') + ' Lakh';
        }
        return '₹' + val.toLocaleString('en-IN');
    }

    function formatImageURL(url) {
        if (!url || typeof url !== 'string') return 'assets/img/property-1.svg';
        url = url.trim();

        // Detect and convert Google Drive sharing links into direct browser-streamable images
        const driveMatch1 = url.match(/\/file\/d\/([a-zA-Z0-9_-]+)/);
        const driveMatch2 = url.match(/[?&]id=([a-zA-Z0-9_-]+)/);
        const driveMatch3 = url.match(/\/d\/([a-zA-Z0-9_-]+)/);
        const driveMatch4 = url.match(/googleusercontent\.com\/d\/([a-zA-Z0-9_-]+)/);

        let fileId = null;
        if (driveMatch1 && driveMatch1[1]) fileId = driveMatch1[1];
        else if (driveMatch2 && driveMatch2[1]) fileId = driveMatch2[1];
        else if (driveMatch3 && driveMatch3[1]) fileId = driveMatch3[1];
        else if (driveMatch4 && driveMatch4[1]) fileId = driveMatch4[1];

        if (fileId) {
            // Direct Google Drive high-resolution thumbnail and CDN stream endpoint
            return `https://drive.google.com/thumbnail?id=${fileId}&sz=w1600`;
        }

        return url;
    }

    function slugify(text) {
        return text.toString().toLowerCase().trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-');
    }

    // Initialize Storage
    function getStoredProperties() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (!raw) {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_PROPERTIES));
                return DEFAULT_PROPERTIES.slice();
            }
            return JSON.parse(raw);
        } catch (e) {
            return DEFAULT_PROPERTIES.slice();
        }
    }

    function saveProperties(props) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(props));
            // Trigger storage change for other tabs
            window.dispatchEvent(new Event('storage'));
        } catch (e) {
            console.error('Failed to save properties to localStorage', e);
        }
    }

    return {
        getAll: function () {
            return getStoredProperties();
        },

        getFeatured: function () {
            return getStoredProperties().filter(p => p.is_featured);
        },

        getById: function (id) {
            const list = getStoredProperties();
            return list.find(p => String(p.id) === String(id)) || null;
        },

        getBySlug: function (slug) {
            const list = getStoredProperties();
            return list.find(p => p.slug === slug) || null;
        },

        add: function (data) {
            const list = getStoredProperties();
            const newId = list.length > 0 ? Math.max(...list.map(p => Number(p.id) || 0)) + 1 : 1;
            
            const newProp = {
                id: newId,
                title: data.title || 'Untitled Property',
                slug: data.slug || slugify(data.title || 'property-' + newId),
                category: data.category || 'Apartment',
                location: data.location || 'Race Course, Coimbatore',
                price: Number(data.price) || 0,
                price_display: data.price_display || formatINR(data.price || 0),
                area_sqft: Number(data.area_sqft) || 0,
                bedrooms: Number(data.bedrooms) || 0,
                bathrooms: Number(data.bathrooms) || 0,
                description: data.description || '',
                status: data.status || 'Available',
                is_featured: data.is_featured ? 1 : 0,
                is_sample: 0, // Real user-added property
                cover_image: formatImageURL(data.cover_image || 'assets/img/property-1.svg'),
                created_at: new Date().toISOString()
            };

            list.unshift(newProp);
            saveProperties(list);
            return newProp;
        },

        update: function (id, data) {
            const list = getStoredProperties();
            const idx = list.findIndex(p => String(p.id) === String(id));
            if (idx === -1) return null;

            if (data.cover_image) {
                data.cover_image = formatImageURL(data.cover_image);
            }

            list[idx] = Object.assign({}, list[idx], data);
            if (data.price && !data.price_display) {
                list[idx].price_display = formatINR(data.price);
            }
            saveProperties(list);
            return list[idx];
        },

        delete: function (id) {
            let list = getStoredProperties();
            list = list.filter(p => String(p.id) !== String(id));
            saveProperties(list);
            return true;
        },

        resetToDefaults: function () {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(DEFAULT_PROPERTIES));
            return DEFAULT_PROPERTIES.slice();
        },

        // Admin Credentials Management
        getAdminAuth: function () {
            try {
                const raw = localStorage.getItem(ADMIN_AUTH_KEY);
                return raw ? JSON.parse(raw) : DEFAULT_AUTH;
            } catch (e) {
                return DEFAULT_AUTH;
            }
        },

        setAdminAuth: function (data) {
            try {
                const current = this.getAdminAuth();
                const updated = Object.assign({}, current, data);
                localStorage.setItem(ADMIN_AUTH_KEY, JSON.stringify(updated));
                return updated;
            } catch (e) {
                return null;
            }
        },

        // Site Settings Management
        getSettings: function () {
            try {
                const raw = localStorage.getItem(SETTINGS_KEY);
                return raw ? JSON.parse(raw) : DEFAULT_SETTINGS;
            } catch (e) {
                return DEFAULT_SETTINGS;
            }
        },

        setSettings: function (data) {
            try {
                const current = this.getSettings();
                const updated = Object.assign({}, current, data);
                localStorage.setItem(SETTINGS_KEY, JSON.stringify(updated));
                return updated;
            } catch (e) {
                return null;
            }
        },

        // Enquiries / Leads Management
        getEnquiries: function () {
            try {
                const raw = localStorage.getItem(ENQUIRIES_KEY);
                if (!raw) {
                    localStorage.setItem(ENQUIRIES_KEY, JSON.stringify(DEFAULT_ENQUIRIES));
                    return DEFAULT_ENQUIRIES.slice();
                }
                return JSON.parse(raw);
            } catch (e) {
                return DEFAULT_ENQUIRIES.slice();
            }
        },

        addEnquiry: function (data) {
            const list = this.getEnquiries();
            const newId = list.length > 0 ? Math.max(...list.map(i => Number(i.id) || 0)) + 1 : 1;
            const newLead = {
                id: newId,
                name: data.name || 'Discreet Client',
                email: data.email || '',
                phone: data.phone || '',
                type: data.type || 'Site Visit',
                property_title: data.property_title || data.property_name || 'Coimbatore Prime Estate',
                preferred_date: data.preferred_date || data.date || 'Immediate',
                message: data.message || data.notes || '',
                status: 'New',
                created_at: new Date().toLocaleDateString('en-IN', { month: 'short', day: 'numeric' }) + ', ' + new Date().toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit' })
            };
            list.unshift(newLead);
            try {
                localStorage.setItem(ENQUIRIES_KEY, JSON.stringify(list));
                // Trigger booking broadcast for Admin Panel
                localStorage.setItem('magaram_latest_site_booking', JSON.stringify({
                    id: newId,
                    client: newLead.name,
                    property: newLead.property_title,
                    type: newLead.type,
                    date: newLead.preferred_date,
                    time: Date.now()
                }));
                window.dispatchEvent(new Event('storage'));
            } catch (e) {}
            return newLead;
        },

        updateEnquiryStatus: function (id, status) {
            const list = this.getEnquiries();
            const idx = list.findIndex(i => String(i.id) === String(id));
            if (idx === -1) return null;
            list[idx].status = status;
            try {
                localStorage.setItem(ENQUIRIES_KEY, JSON.stringify(list));
                window.dispatchEvent(new Event('storage'));
            } catch (e) {}
            return list[idx];
        },

        deleteEnquiry: function (id) {
            let list = this.getEnquiries();
            list = list.filter(i => String(i.id) !== String(id));
            try {
                localStorage.setItem(ENQUIRIES_KEY, JSON.stringify(list));
                window.dispatchEvent(new Event('storage'));
            } catch (e) {}
            return true;
        },

        exportEnquiriesCSV: function () {
            const list = this.getEnquiries();
            const headers = ['ID', 'Date', 'Name', 'Phone', 'Email', 'Type', 'Property Interest', 'Preferred Date', 'Status', 'Message'];
            const rows = list.map(i => [
                i.id,
                `"${(i.created_at || '').replace(/"/g, '""')}"`,
                `"${(i.name || '').replace(/"/g, '""')}"`,
                `"${(i.phone || '').replace(/"/g, '""')}"`,
                `"${(i.email || '').replace(/"/g, '""')}"`,
                `"${(i.type || '').replace(/"/g, '""')}"`,
                `"${(i.property_title || '').replace(/"/g, '""')}"`,
                `"${(i.preferred_date || '').replace(/"/g, '""')}"`,
                `"${(i.status || '').replace(/"/g, '""')}"`,
                `"${(i.message || '').replace(/"/g, '""')}"`
            ]);

            const csvContent = [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `magaram_bookings_${new Date().toISOString().slice(0, 10)}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },

        formatImageURL: formatImageURL,
        formatINR: formatINR
    };
}));

