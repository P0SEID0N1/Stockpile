# Stockpile Apple Clients

This directory contains the shared Swift package and native Apple app scaffolding.

- `Sources/PortfolioCore` holds DTOs and the API client shared by iPhone, iPad, and Mac.
- `Sources/PortfolioAppleUI` holds cross-platform SwiftUI screens and app state.
- `Sources/PortfolioMacApp` is a buildable macOS executable target for local SwiftPM-based validation.
- `Apps/iOS/StockpileMobileApp.swift` is the iPhone/iPad app entrypoint source intended to be added to an Xcode iOS target when full Xcode is available.

The current environment does not include the full Xcode toolchain, so iOS target generation is scaffolded as source rather than a verified Xcode project.
