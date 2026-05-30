import Foundation

public struct PortfolioSummary: Codable, Equatable, Sendable {
    public let portfolioID: Int
    public let portfolioName: String
    public let benchmarkSymbol: String
    public let benchmarkLabel: String?
    public let currentValue: Double
    public let costBasisTotal: Double
    public let totalGainLoss: Double
    public let dayChange: Double
    public let dayChangePercent: Double
    public let holdingsCount: Int
    public let accountsCount: Int
    public let quoteTimestamp: Date?
    public let assetTypeAllocation: [AllocationSlice]
    public let accountAllocation: [AllocationSlice]

    enum CodingKeys: String, CodingKey {
        case portfolioID = "portfolio_id"
        case portfolioName = "portfolio_name"
        case benchmarkSymbol = "benchmark_symbol"
        case benchmarkLabel = "benchmark_label"
        case currentValue = "current_value"
        case costBasisTotal = "cost_basis_total"
        case totalGainLoss = "total_gain_loss"
        case dayChange = "day_change"
        case dayChangePercent = "day_change_percent"
        case holdingsCount = "holdings_count"
        case accountsCount = "accounts_count"
        case quoteTimestamp = "quote_timestamp"
        case assetTypeAllocation = "asset_type_allocation"
        case accountAllocation = "account_allocation"
    }
}

public struct AllocationSlice: Codable, Equatable, Identifiable, Sendable {
    public let label: String
    public let value: Double
    public let percentage: Double

    public var id: String { label }
}

public struct HoldingRow: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let account: AccountSummary
    public let asset: AssetSummary
    public let quantity: String
    public let costBasisTotal: String
    public let marketValue: String?

    enum CodingKeys: String, CodingKey {
        case id
        case account
        case asset
        case quantity
        case costBasisTotal = "cost_basis_total"
        case marketValue = "market_value"
    }
}

public struct HoldingListResponse: Codable, Equatable, Sendable {
    public let quoteTimestamp: String?
    public let data: [HoldingRow]

    enum CodingKeys: String, CodingKey {
        case quoteTimestamp = "quote_timestamp"
        case data
    }
}

public struct AccountSummary: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let name: String
    public let type: String
    public let currency: String
}

public struct AssetSummary: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let symbol: String?
    public let name: String
    public let assetType: String

    enum CodingKeys: String, CodingKey {
        case id
        case symbol
        case name
        case assetType = "asset_type"
    }
}

public struct PortfolioPoint: Codable, Equatable, Identifiable, Sendable {
    public let date: String
    public let value: Double

    public var id: String { date }
}

public struct PerformanceSeries: Codable, Equatable, Sendable {
    public let range: String
    public let portfolio: [PortfolioPoint]
    public let comparisonPortfolio: [PortfolioPoint]
    public let benchmark: [PortfolioPoint]
    public let portfolioReturnPercent: Double
    public let benchmarkReturnPercent: Double
    public let benchmarkSymbol: String
    public let benchmarkLabel: String

    enum CodingKeys: String, CodingKey {
        case range
        case portfolio
        case comparisonPortfolio = "comparison_portfolio"
        case benchmark
        case portfolioReturnPercent = "portfolio_return_percent"
        case benchmarkReturnPercent = "benchmark_return_percent"
        case benchmarkSymbol = "benchmark_symbol"
        case benchmarkLabel = "benchmark_label"
    }
}

public struct JournalEntryDTO: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let entryType: String
    public let tradeDate: String
    public let quantity: String?
    public let pricePerUnit: String?
    public let amount: String?
    public let sourceType: String
    public let notes: String?
    public let account: AccountSummary?
    public let asset: AssetSummary?
    public let holding: JournalHolding?

    enum CodingKeys: String, CodingKey {
        case id
        case entryType = "entry_type"
        case tradeDate = "trade_date"
        case quantity
        case pricePerUnit = "price_per_unit"
        case amount
        case sourceType = "source_type"
        case notes
        case account
        case asset
        case holding
    }
}

public struct JournalHolding: Codable, Equatable, Sendable {
    public let id: Int
    public let asset: AssetSummary
}

public struct PlannerTarget: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let label: String
    public let assetType: String?
    public let targetPercentage: Double
    public let currentPercentage: Double
    public let currentValue: Double
    public let targetValue: Double
    public let deltaValue: Double

    enum CodingKeys: String, CodingKey {
        case id
        case label
        case assetType = "asset_type"
        case targetPercentage = "target_percentage"
        case currentPercentage = "current_percentage"
        case currentValue = "current_value"
        case targetValue = "target_value"
        case deltaValue = "delta_value"
    }
}

public struct PlannerResponse: Codable, Equatable, Sendable {
    public let currentValue: Double
    public let targets: [PlannerTarget]

    enum CodingKeys: String, CodingKey {
        case currentValue = "current_value"
        case targets
    }
}

public struct SessionPayload: Codable, Equatable, Sendable {
    public let token: String
    public let tokenType: String
    public let expiresAt: Date?
    public let user: SessionUser
    public let portfolios: [PortfolioDescriptor]

    enum CodingKeys: String, CodingKey {
        case token
        case tokenType = "token_type"
        case expiresAt = "expires_at"
        case user
        case portfolios
    }
}

public struct CurrentSessionPayload: Codable, Equatable, Sendable {
    public let user: SessionUser
    public let portfolios: [PortfolioDescriptor]
}

public struct SessionUser: Codable, Equatable, Sendable {
    public let id: Int
    public let name: String
    public let email: String
}

public struct PortfolioDescriptor: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let name: String
    public let baseCurrency: String

    enum CodingKeys: String, CodingKey {
        case id
        case name
        case baseCurrency = "base_currency"
    }
}

public struct ResetPortfolioResponse: Codable, Equatable, Sendable {
    public let message: String
    public let portfolio: PortfolioDescriptor
}
