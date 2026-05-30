import Foundation

public struct PortfolioSummary: Codable, Equatable, Sendable {
    public let portfolioID: Int
    public let portfolioName: String
    public let benchmarkSymbol: String
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
    public let portfolio: [PortfolioPoint]
    public let benchmark: [PortfolioPoint]
}

public struct JournalEntryDTO: Codable, Equatable, Identifiable, Sendable {
    public let id: Int
    public let entryType: String
    public let tradeDate: String
    public let amount: String?
    public let notes: String?
    public let account: AccountSummary?
    public let holding: JournalHolding?

    enum CodingKeys: String, CodingKey {
        case id
        case entryType = "entry_type"
        case tradeDate = "trade_date"
        case amount
        case notes
        case account
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
