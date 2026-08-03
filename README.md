# Pager System

Webbaseret system til administration af pagere, brandfolk og stationer for beredskabet.


<img width="1398" height="694" alt="image" src="https://github.com/user-attachments/assets/10581d31-eacf-45ca-8042-e3fb0fc6511d" />




## Features

- **Pager-administration** – Opret, rediger, arkiver og spor pagere gennem hele livscyklussen
- **Workflow-håndtering** – Reserver, udlever, returner og send til reparation/klargøring
- **SIM-kort tracking** – Tilknyt telefonnumre med fuld historik
- **Brandfolk** – Administrer personale med stationstilknytninger og kompetencer
- **Kompetencer** – Tildel kompetencer til brandfolk og spor udløbsdatoer
- **Stationer** – Organisér brandfolk og pagere pr. station
- **Søgning** – Live søgning på pagere, brandfolk og stationer
- **Rapporter** – Statusoverblik, telefonnummerlister, manglende pagere og CSV-eksport
- **Rollebaseret adgang** – Admin, global læser, station læser
- **Audit log** – Fuld sporbarhed på alle handlinger

## Krav

- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.6+
- Apache med mod_rewrite

## Installation

```bash
git clone https://github.com/HrCalmar/PagerSystem.git
cd pager-system
cp .env.example .env
```

Rediger `.env` med dine database-credentials:

```env
DB_HOST=localhost
DB_NAME=pager_system
DB_USER=din_bruger
DB_PASS=dit_password
```

Importér databasen:

```bash
mysql -u root -p pager_system < database/schema.sql
```

Opret første admin-bruger:

```bash
php scripts/create_user.php
```

## Mappestruktur

```
├── public/             # Document root
│   ├── index.php       # Entry point
│   └── assets/         # CSS, JS
├── src/
│   ├── Config/         # Database config
│   ├── Controllers/    # Request handlers
│   ├── Core/           # Auth, Router, Session, CSRF
│   ├── Helpers/        # Status badges m.m.
│   ├── Middleware/     # Auth & rolle-check
│   └── Services/       # Business logic
├── views/              # PHP templates
├── scripts/            # CLI værktøjer
└── .env                # Miljøvariabler
```

## Brugerroller

| Rolle | Rettigheder |
|-------|-------------|
| `admin` | Fuld adgang til alt |
| `global_read` | Kan se alt, ingen redigering |
| `station_read` | Kun adgang til egen station |

## Pager Status Flow

```
in_stock → reserved ──┐
    ↑                  ↓
    │               issued
    │            ↙     ↓     ↘
    │       in_stock  in_repair  for_preparation
    │                   ↓              ↓
    └───────────────────┴──────────────┘

Fra issued eller in_repair: → defect
```

## Licens

MIT
