# Software Cost Metrics (Chapter 7) - MindSpace Implementation

## Overview
This document maps the chapter concepts from Software Cost Metrics to the implemented MindSpace module.

## Covered Topics from the Software cost metric
1. Cost models vs constraint models
2. Estimation techniques (bottom-up, top-down, expert judgement, analogy, pricing-to-win, Parkinson, algorithmic)
3. COCOMO and COCOMO II
4. SLIM (Putnam-style schedule-effort constraints)
5. Advantages and drawbacks of major model families

## Where It Is Implemented
1. Cost data API: `metrics/cost_data.php`
2. Interactive dashboard: `metrics/cost_dashboard.php`
3. Admin quick access tile: `admin/index.html`
4. Database schema and seed data: `database/migration_cost_metrics.sql`

## Core Equations Included

### 1) Regression-style cost models
- E = a + b * S^c
- Where E = effort, S = size

### 2) COCOMO (original)
- Basic: E = a * KLOC^b
- Intermediate: E = a * KLOC^b * EAF
- Schedule: Tdev = c * E^d

Implemented mode constants:
- Basic:
  - Organic: a=2.4, b=1.05
  - Semi-detached: a=3.0, b=1.12
  - Embedded: a=3.6, b=1.20
- Intermediate:
  - Organic: a=3.2, b=1.05, c=2.5, d=0.38
  - Semi-detached: a=3.0, b=1.12, c=2.5, d=0.35
  - Embedded: a=2.8, b=1.20, c=2.5, d=0.32

### 3) COCOMO II
- Application Composition: E = OP / PROD
- Early Design: E = 2.45 * KLOC * EAF
- Post-Architecture: E = 2.45 * (KLOC^b) * EAF
- Scale exponent: b = 0.91 + 0.01 * sum(SF_i)

### 4) SLIM (constraint style)
- B = (S/C)^3 / T^4
- E = 0.3945 * B
- D = B / T^3

Where:
- S = size
- C = process productivity parameter
- T = delivery time
- B/E = effort terms from model
- D = manpower acceleration

## Practical KPI Layer Added
Alongside textbook models, the project tracks practical delivery cost KPIs:
1. Actual cost = sum(actual_hours * hourly_rate)
2. Planned cost = sum(planned_hours * hourly_rate)
3. Estimation variance %
4. Rework %
5. Cost per FP and FP per hour (when FP data exists)

## Notes for Academic Reporting
1. Estimation outputs depend strongly on size quality (KLOC/FP/OP assumptions).
2. Cost driver and scale factor ratings are subjective and should be calibrated with local project history.
3. Keep predicted vs actual comparisons to continuously adjust constants and improve future estimates.
