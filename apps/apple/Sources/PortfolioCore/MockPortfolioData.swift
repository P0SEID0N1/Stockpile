import Foundation

public enum MockPortfolioData {
    public static let summary = PortfolioSummary(
        portfolioID: 1,
        portfolioName: "Main Portfolio",
        benchmarkSymbol: "W5000",
        benchmarkLabel: "Wilshire 5000",
        currentValue: 128_450.23,
        costBasisTotal: 113_018.55,
        totalGainLoss: 15_431.68,
        dayChange: 624.41,
        dayChangePercent: 0.49,
        holdingsCount: 7,
        accountsCount: 3,
        quoteTimestamp: .now,
        assetTypeAllocation: [
            AllocationSlice(label: "stocks", value: 61_200, percentage: 47.6),
            AllocationSlice(label: "etf", value: 33_400, percentage: 26.0),
            AllocationSlice(label: "mutual fund", value: 18_000, percentage: 14.0),
            AllocationSlice(label: "cash", value: 15_850.23, percentage: 12.4),
        ],
        accountAllocation: [
            AllocationSlice(label: "Brokerage", value: 72_310.22, percentage: 56.3),
            AllocationSlice(label: "Roth IRA", value: 33_040.01, percentage: 25.7),
            AllocationSlice(label: "401(k)", value: 23_100, percentage: 18.0),
        ]
    )

    public static let holdings = HoldingListResponse(
        quoteTimestamp: ISO8601DateFormatter().string(from: .now),
        data: [
            HoldingRow(id: 1, account: AccountSummary(id: 1, name: "Brokerage", type: "taxable", currency: "USD"), asset: AssetSummary(id: 1, symbol: "AAPL", name: "Apple Inc.", assetType: "stocks"), quantity: "72.000000", costBasisTotal: "12485.13", marketValue: "13896.00"),
            HoldingRow(id: 2, account: AccountSummary(id: 1, name: "Brokerage", type: "taxable", currency: "USD"), asset: AssetSummary(id: 2, symbol: "VTI", name: "Vanguard Total Stock Market ETF", assetType: "etf"), quantity: "110.000000", costBasisTotal: "24210.50", marketValue: "30195.00"),
            HoldingRow(id: 3, account: AccountSummary(id: 2, name: "Roth IRA", type: "retirement", currency: "USD"), asset: AssetSummary(id: 3, symbol: "BND", name: "Vanguard Total Bond Market ETF", assetType: "bonds"), quantity: "95.000000", costBasisTotal: "6810.00", marketValue: "7049.25"),
        ]
    )

    public static let series = PerformanceSeries(
        range: "1m",
        portfolio: [
            PortfolioPoint(date: "2026-05-01", value: 121_400),
            PortfolioPoint(date: "2026-05-08", value: 122_950),
            PortfolioPoint(date: "2026-05-15", value: 124_880),
            PortfolioPoint(date: "2026-05-22", value: 126_110),
            PortfolioPoint(date: "2026-05-29", value: 128_450.23),
        ],
        comparisonPortfolio: [
            PortfolioPoint(date: "2026-05-01", value: 100),
            PortfolioPoint(date: "2026-05-08", value: 101.28),
            PortfolioPoint(date: "2026-05-15", value: 102.87),
            PortfolioPoint(date: "2026-05-22", value: 103.88),
            PortfolioPoint(date: "2026-05-29", value: 105.81),
        ],
        benchmark: [
            PortfolioPoint(date: "2026-05-01", value: 100),
            PortfolioPoint(date: "2026-05-08", value: 101.8),
            PortfolioPoint(date: "2026-05-15", value: 102.6),
            PortfolioPoint(date: "2026-05-22", value: 103.1),
            PortfolioPoint(date: "2026-05-29", value: 104.4),
        ],
        portfolioReturnPercent: 5.81,
        benchmarkReturnPercent: 4.40,
        benchmarkSymbol: "W5000",
        benchmarkLabel: "Wilshire 5000"
    )

    public static let journal: [JournalEntryDTO] = [
        JournalEntryDTO(id: 1, entryType: "buy", tradeDate: "2026-05-15", quantity: "12.000000", pricePerUnit: "266.666667", amount: "3200.00", sourceType: "manual", notes: "Added to VTI after monthly contribution.", account: AccountSummary(id: 1, name: "Brokerage", type: "taxable", currency: "USD"), asset: AssetSummary(id: 2, symbol: "VTI", name: "Vanguard Total Stock Market ETF", assetType: "etf"), holding: JournalHolding(id: 2, asset: AssetSummary(id: 2, symbol: "VTI", name: "Vanguard Total Stock Market ETF", assetType: "etf"))),
        JournalEntryDTO(id: 2, entryType: "dividend", tradeDate: "2026-05-18", quantity: nil, pricePerUnit: nil, amount: "44.22", sourceType: "auto_dividend", notes: "Quarterly dividend recorded automatically.", account: AccountSummary(id: 2, name: "Roth IRA", type: "retirement", currency: "USD"), asset: AssetSummary(id: 3, symbol: "BND", name: "Vanguard Total Bond Market ETF", assetType: "bonds"), holding: nil),
        JournalEntryDTO(id: 3, entryType: "dividend_reinvested", tradeDate: "2026-05-18", quantity: "0.580000", pricePerUnit: "76.241379", amount: "44.22", sourceType: "auto_dividend", notes: "Dividend automatically reinvested.", account: AccountSummary(id: 2, name: "Roth IRA", type: "retirement", currency: "USD"), asset: AssetSummary(id: 3, symbol: "BND", name: "Vanguard Total Bond Market ETF", assetType: "bonds"), holding: nil),
    ]

    public static let planner = PlannerResponse(
        currentValue: 128_450.23,
        targets: [
            PlannerTarget(id: 1, label: "stocks", assetType: "stocks", targetPercentage: 50, currentPercentage: 47.6, currentValue: 61_200, targetValue: 64_225.12, deltaValue: 3_025.12),
            PlannerTarget(id: 2, label: "etf", assetType: "etf", targetPercentage: 30, currentPercentage: 26.0, currentValue: 33_400, targetValue: 38_535.07, deltaValue: 5_135.07),
            PlannerTarget(id: 3, label: "bonds", assetType: "bonds", targetPercentage: 12, currentPercentage: 8.5, currentValue: 10_950, targetValue: 15_414.03, deltaValue: 4_464.03),
        ]
    )
}
