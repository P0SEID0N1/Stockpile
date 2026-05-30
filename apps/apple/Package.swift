// swift-tools-version: 6.2

import PackageDescription

let package = Package(
    name: "StockpileApple",
    platforms: [
        .macOS(.v14),
        .iOS(.v17),
    ],
    products: [
        .library(name: "PortfolioCore", targets: ["PortfolioCore"]),
        .library(name: "PortfolioAppleUI", targets: ["PortfolioAppleUI"]),
        .executable(name: "PortfolioMacApp", targets: ["PortfolioMacApp"]),
    ],
    targets: [
        .target(name: "PortfolioCore"),
        .target(
            name: "PortfolioAppleUI",
            dependencies: ["PortfolioCore"]
        ),
        .executableTarget(
            name: "PortfolioMacApp",
            dependencies: ["PortfolioAppleUI", "PortfolioCore"]
        ),
        .testTarget(
            name: "PortfolioCoreTests",
            dependencies: ["PortfolioCore"]
        ),
    ],
)
