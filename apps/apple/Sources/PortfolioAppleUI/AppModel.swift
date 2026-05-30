import Foundation
import Observation
import PortfolioCore

@MainActor
@Observable
public final class PortfolioAppModel {
    public enum Section: String, CaseIterable, Identifiable {
        case dashboard
        case portfolio
        case journal
        case plan
        case settings

        public var id: String { rawValue }

        public var title: String {
            switch self {
            case .dashboard: "Dashboard"
            case .portfolio: "Portfolio"
            case .journal: "Journal"
            case .plan: "Plan"
            case .settings: "Settings"
            }
        }

        public var symbolName: String {
            switch self {
            case .dashboard: "chart.line.uptrend.xyaxis"
            case .portfolio: "briefcase"
            case .journal: "book.closed"
            case .plan: "scope"
            case .settings: "gearshape"
            }
        }
    }

    public var selectedSection: Section = .dashboard
    public var summary: PortfolioSummary = MockPortfolioData.summary
    public var holdings: [HoldingRow] = MockPortfolioData.holdings.data
    public var performanceSeries: PerformanceSeries = MockPortfolioData.series
    public var journalEntries: [JournalEntryDTO] = MockPortfolioData.journal
    public var planner: PlannerResponse = MockPortfolioData.planner
    public var serverURL: String = "https://your-server.example.com"
    public var isRefreshing = false
    public var errorMessage: String?

    private let apiClient: PortfolioAPIClient?

    public init(apiClient: PortfolioAPIClient? = nil) {
        self.apiClient = apiClient
    }

    public func refresh() async {
        guard let apiClient else {
            return
        }

        isRefreshing = true
        defer { isRefreshing = false }

        do {
            async let summary = apiClient.fetchSummary()
            async let holdings = apiClient.fetchHoldings()
            async let series = apiClient.fetchPerformanceSeries()
            async let journal = apiClient.fetchJournal()
            async let planner = apiClient.fetchPlanner()

            self.summary = try await summary
            self.holdings = try await holdings.data
            self.performanceSeries = try await series
            self.journalEntries = try await journal
            self.planner = try await planner
            self.errorMessage = nil
        } catch {
            self.errorMessage = error.localizedDescription
        }
    }
}
