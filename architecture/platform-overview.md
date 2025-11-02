# Trident Systems Platform Architecture

## Overview

Comprehensive water utility management platform built on modern microservices architecture with shared data models and unified API gateway.

## Platform Components

### 🔥 HydrantHub
**Status**: Production Ready  
**Repository**: [hydrant-hub](https://github.com/rcabral85/hydrant-hub)

- NFPA 291 compliant flow testing
- Mobile field data collection
- PDF report generation
- GIS mapping and export
- Preventive maintenance scheduling

### 🔧 Pipeline Manager
**Status**: MVP Development  
**Repository**: [pipeline-manager](https://github.com/rcabral85/pipeline-manager)

- Water main and valve asset registry
- Condition assessment and tracking
- Work order management
- Break/leak history and analytics
- Rehabilitation planning

### 🗺️ GIS Service
**Status**: Planning  
**Repository**: [gis-service](https://github.com/rcabral85/gis-service)

- Spatial data import/export (GeoJSON, KML, Shapefile)
- Esri Feature Service integration
- Coordinate system transformations
- Spatial indexing and queries
- Web mapping components

### 📱 FieldKit
**Status**: Design Phase  
**Repository**: [fieldkit](https://github.com/rcabral85/fieldkit)

- Cross-platform mobile app (iOS/Android)
- Offline-first data collection
- Photo/video capture with GPS tagging
- QR code and barcode scanning
- Real-time sync when connected

### 📋 Compliance Engine
**Status**: Planning  
**Repository**: [compliance-engine](https://github.com/rcabral85/compliance-engine)

- Regulatory rule engine (NFPA 291, O. Reg 169/03)
- Automated task scheduling and deadlines
- Audit trail and evidence management
- Compliance reporting and templates
- Regulatory change notifications

### 🤖 Analytics AI
**Status**: Research  
**Repository**: [analytics-ai](https://github.com/rcabral85/analytics-ai)

- Asset failure prediction models
- Maintenance optimization algorithms
- Investment planning and ROI analysis
- Performance dashboards and KPIs
- Trend analysis and reporting

### 🌐 API Gateway
**Status**: Foundation  
**Repository**: [api-gateway](https://github.com/rcabral85/api-gateway)

- Unified authentication and authorization
- Multi-tenant architecture
- Rate limiting and usage tracking
- Service discovery and routing
- API documentation and developer portal

## Shared Data Models

### Core Entities

```sql
-- Organizations and Tenancy
organizations (id, name, type, settings, created_at)
users (id, email, name, organization_id, roles, created_at)
user_sessions (id, user_id, token, expires_at)

-- Asset Management
assets (id, organization_id, type, identifier, location, properties, created_at)
asset_types (id, name, category, schema, validation_rules)
locations (id, organization_id, geometry, address, properties)

-- Operations
inspections (id, asset_id, user_id, type, data, status, completed_at)
work_orders (id, organization_id, asset_id, type, priority, status, assigned_to)
flow_tests (id, asset_id, user_id, test_data, calculations, report_url)

-- Compliance
compliance_tasks (id, organization_id, rule_id, due_date, status, evidence)
regulatory_rules (id, jurisdiction, regulation, requirements, schedule)
audit_events (id, user_id, action, resource, changes, timestamp)
```

### Spatial Data (PostGIS)

```sql
-- All assets have spatial geometry
ALTER TABLE assets ADD COLUMN geometry GEOMETRY(POINT, 4326);
ALTER TABLE locations ADD COLUMN geometry GEOMETRY(POLYGON, 4326);

-- Spatial indexes for performance
CREATE INDEX idx_assets_geometry ON assets USING GIST (geometry);
CREATE INDEX idx_locations_geometry ON locations USING GIST (geometry);
```

## Technology Stack

### Backend Services
- **Runtime**: Node.js 18+ with TypeScript
- **Framework**: Express.js with middleware architecture
- **Database**: PostgreSQL 14+ with PostGIS extension
- **Authentication**: JWT tokens with refresh token rotation
- **File Storage**: AWS S3 or compatible (MinIO for development)
- **Background Jobs**: Bull Queue with Redis

### Frontend Applications
- **Web Apps**: React 18 with TypeScript and Material-UI
- **Mobile Apps**: React Native with native modules
- **Mapping**: Leaflet with custom plugins and tile layers
- **Charts**: Chart.js and D3.js for visualizations
- **State Management**: Zustand for client state

### Infrastructure
- **Container Platform**: Docker with multi-stage builds
- **Orchestration**: Docker Compose (development), Kubernetes (production)
- **Cloud Hosting**: Railway (current), AWS/GCP (future)
- **CDN**: Cloudflare for static assets and API acceleration
- **Monitoring**: Prometheus + Grafana for metrics and alerting

### Development & DevOps
- **Version Control**: Git with GitHub and GitHub Actions
- **CI/CD**: Automated testing, building, and deployment
- **Package Management**: npm workspaces for monorepo structure
- **Code Quality**: ESLint, Prettier, and automated testing
- **Documentation**: OpenAPI specs and automated doc generation

## Integration Architecture

### Internal Service Communication
- **Synchronous**: REST APIs through API Gateway
- **Asynchronous**: Event-driven with message queues
- **Data Sharing**: Shared database with service-specific schemas
- **Authentication**: JWT tokens validated by API Gateway

### External Integrations

#### Municipal GIS Systems
- **Esri ArcGIS**: Feature Service REST API
- **QGIS**: Plugin for direct data exchange
- **Generic**: GeoJSON/KML export for any system

#### Asset Management Systems
- **Cartegraph**: REST API integration
- **CityWorks**: SOAP/REST API connections
- **Maximo**: Standard REST API endpoints
- **Custom**: Flexible CSV/Excel import/export

#### SCADA Systems
- **Wonderware**: OPC-UA or REST API
- **Ignition**: Real-time data feeds
- **Generic**: Modbus TCP or DNP3 protocols

## Security Architecture

### Authentication & Authorization
- **Multi-Factor Authentication**: TOTP and SMS for admin users
- **Role-Based Access Control**: Granular permissions per organization
- **API Key Management**: Service-to-service authentication
- **Session Management**: Secure token storage and rotation

### Data Protection
- **Encryption at Rest**: AES-256 for database and file storage
- **Encryption in Transit**: TLS 1.3 for all API communications
- **Data Isolation**: Complete tenant separation at database level
- **Audit Logging**: Immutable logs for all data access and changes

### Compliance & Privacy
- **PIPEDA Compliance**: Canadian privacy law adherence
- **Data Retention**: Configurable retention policies per organization
- **Right to Deletion**: Complete data removal capabilities
- **Export Capabilities**: Full data export in standard formats

## Scalability & Performance

### Horizontal Scaling
- **Stateless Services**: All services designed for horizontal scaling
- **Database Sharding**: Tenant-based sharding for large deployments
- **CDN Distribution**: Global content delivery for improved performance
- **Load Balancing**: Application-level load balancing with health checks

### Performance Optimization
- **Caching Strategy**: Redis for session and frequently accessed data
- **Database Optimization**: Proper indexing and query optimization
- **Image Processing**: Optimized image storage and delivery
- **Mobile Optimization**: Offline-first architecture with sync

## Deployment Architecture

### Environment Strategy
- **Development**: Local Docker Compose setup
- **Staging**: Cloud deployment mirroring production
- **Production**: Multi-region deployment with failover
- **Disaster Recovery**: Automated backups and restoration procedures

### Release Process
- **Feature Branches**: Git flow with pull request reviews
- **Automated Testing**: Unit, integration, and end-to-end tests
- **Blue-Green Deployment**: Zero-downtime deployments
- **Rollback Capabilities**: Quick rollback to previous versions

---

*This architecture supports the vision of a comprehensive, scalable, and maintainable water utility management platform.*