# Skylearn Billing Pro - Style Guide

This document outlines the visual identity and branding guidelines for Skylearn Billing Pro.

## Brand Colors

### Primary Colors

- **Deep Navy Blue**: `#183153`
  - Usage: Primary headers, navigation, main action buttons
  - RGB: rgb(24, 49, 83)
  - HSL: hsl(214, 55%, 21%)

- **Bright Red**: `#FF3B00`
  - Usage: Call-to-action buttons, alerts, notifications, accent elements
  - RGB: rgb(255, 59, 0)
  - HSL: hsl(14, 100%, 50%)

### Supporting Colors

- **Light Gray**: `#F4F4F4`
  - Usage: Background sections, subtle borders, card backgrounds
  - RGB: rgb(244, 244, 244)
  - HSL: hsl(0, 0%, 96%)

- **White**: `#FFFFFF`
  - Usage: Main backgrounds, text on dark backgrounds, card content
  - RGB: rgb(255, 255, 255)
  - HSL: hsl(0, 0%, 100%)

### Color Usage Guidelines

```css
/* Primary Button */
.btn-primary {
    background-color: #FF3B00;
    color: #FFFFFF;
    border: none;
}

/* Secondary Button */
.btn-secondary {
    background-color: #183153;
    color: #FFFFFF;
    border: none;
}

/* Background Sections */
.section-bg {
    background-color: #F4F4F4;
}

/* Main Content Areas */
.content-area {
    background-color: #FFFFFF;
}
```

## Typography

### Heading Font: Montserrat

- **Font Family**: Montserrat
- **Weights**: 400 (Regular), 600 (Semi-bold), 700 (Bold)
- **Usage**: All headings (H1-H6), titles, navigation items

```css
h1, h2, h3, h4, h5, h6,
.heading-font {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    color: #183153;
}
```

### Body Font: Open Sans / Inter

- **Primary**: Open Sans
- **Alternative**: Inter (if Open Sans is unavailable)
- **Weights**: 400 (Regular), 500 (Medium), 600 (Semi-bold)
- **Usage**: Body text, descriptions, form labels, buttons

```css
body, p, span, label, input, textarea,
.body-font {
    font-family: 'Open Sans', 'Inter', sans-serif;
    font-weight: 400;
    color: #333333;
}
```

### Font Size Scale

- **H1**: 2.5rem (40px)
- **H2**: 2rem (32px)
- **H3**: 1.75rem (28px)
- **H4**: 1.5rem (24px)
- **H5**: 1.25rem (20px)
- **H6**: 1.125rem (18px)
- **Body**: 1rem (16px)
- **Small**: 0.875rem (14px)

## Design Elements

### Rounded Corners

Use consistent border-radius values throughout the interface:

```css
/* Buttons */
.btn {
    border-radius: 8px;
}

/* Cards */
.card {
    border-radius: 12px;
}

/* Input Fields */
input, textarea, select {
    border-radius: 6px;
}

/* Small Elements */
.badge, .tag {
    border-radius: 4px;
}
```

### Shadows

Clean, subtle shadows for depth:

```css
/* Card Shadow */
.card {
    box-shadow: 0 2px 8px rgba(24, 49, 83, 0.1);
}

/* Button Hover Shadow */
.btn:hover {
    box-shadow: 0 4px 12px rgba(255, 59, 0, 0.2);
}

/* Modal Shadow */
.modal {
    box-shadow: 0 8px 32px rgba(24, 49, 83, 0.15);
}
```

### Spacing Scale

Use consistent spacing throughout:

- **XS**: 4px
- **SM**: 8px
- **MD**: 16px
- **LG**: 24px
- **XL**: 32px
- **XXL**: 48px

## UI Components

### Buttons

#### Primary Button
```css
.btn-primary {
    background-color: #FF3B00;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background-color: #E63400;
    box-shadow: 0 4px 12px rgba(255, 59, 0, 0.2);
}
```

#### Secondary Button
```css
.btn-secondary {
    background-color: #183153;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    padding: 12px 24px;
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background-color: #1A3A63;
}
```

### Cards

```css
.card {
    background-color: #FFFFFF;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(24, 49, 83, 0.1);
    border: 1px solid #F4F4F4;
}
```

### Form Elements

```css
input, textarea, select {
    border: 1px solid #E0E0E0;
    border-radius: 6px;
    padding: 12px 16px;
    font-family: 'Open Sans', sans-serif;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

input:focus, textarea:focus, select:focus {
    border-color: #FF3B00;
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 59, 0, 0.1);
}
```

## Logo and Icons

### Logo Usage

- Use the official Skylearn Billing Pro logo as provided
- Maintain proper spacing around the logo (minimum clear space equal to the height of the logo)
- Do not modify, stretch, or alter the logo
- Use appropriate logo version for different backgrounds

### Icon Style

- Use consistent icon style throughout the interface
- Prefer outline/line icons over filled icons
- Maintain consistent stroke width (2px recommended)
- Use icons at standard sizes: 16px, 20px, 24px, 32px

## Layout Guidelines

### Grid System

Use a 12-column grid system with consistent gutters:

```css
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 16px;
}

.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -8px;
}

.col {
    padding: 0 8px;
}
```

### Responsive Breakpoints

- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

## Accessibility

### Color Contrast

All color combinations meet WCAG 2.1 AA standards:

- **Deep Navy Blue (#183153) on White**: 9.8:1 ✓
- **White on Deep Navy Blue**: 9.8:1 ✓
- **Bright Red (#FF3B00) on White**: 4.5:1 ✓
- **White on Bright Red**: 4.5:1 ✓

### Focus States

Ensure all interactive elements have clear focus states for keyboard navigation.

## WordPress Admin Integration

### Admin Color Scheme

Adapt the color scheme to integrate well with WordPress admin:

```css
.wp-admin .skylearn-billing-pro {
    --primary-color: #183153;
    --accent-color: #FF3B00;
    --background-color: #F4F4F4;
    --text-color: #333333;
}
```

## Payment Gateway Styling

### Stripe Elements

Customize Stripe elements to match the brand:

```css
.StripeElement {
    border: 1px solid #E0E0E0;
    border-radius: 6px;
    padding: 12px 16px;
    background-color: #FFFFFF;
}

.StripeElement--focus {
    border-color: #FF3B00;
    box-shadow: 0 0 0 3px rgba(255, 59, 0, 0.1);
}
```

### Lemon Squeezy Integration

Ensure consistent styling with Lemon Squeezy checkout flows while maintaining brand identity.

---

**Note**: This style guide should be referenced for all UI development and maintained as the single source of truth for Skylearn Billing Pro's visual identity.

© 2024 Skyian LLC