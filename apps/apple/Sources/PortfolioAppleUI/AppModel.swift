import Foundation
import Observation
import PortfolioCore

@MainActor
@Observable
public final class PortfolioAppModel {
    public enum Section: String, CaseIterable, Identifiable {
        case dashboard
        case portfolio
        case performance
        case journal
        case settings

        public var id: String { rawValue }

        public var title: String {
            switch self {
            case .dashboard: "Dashboard"
            case .portfolio: "Portfolio"
            case .performance: "Performance"
            case .journal: "Journal"
            case .settings: "Settings"
            }
        }

        public var symbolName: String {
            switch self {
            case .dashboard: "chart.line.uptrend.xyaxis"
            case .portfolio: "briefcase"
            case .performance: "globe.americas"
            case .journal: "book.closed"
            case .settings: "gearshape"
            }
        }
    }

    public enum AuthenticationState: Equatable {
        case signedOut
        case signingIn
        case signedIn
    }

    public var selectedSection: Section = .dashboard
    public var summary: PortfolioSummary = MockPortfolioData.summary
    public var holdings: [HoldingRow] = MockPortfolioData.holdings.data
    public var performanceSeries: PerformanceSeries = MockPortfolioData.series
    public var journalEntries: [JournalEntryDTO] = MockPortfolioData.journal
    public var selectedPerformanceRange = "1m"

    public var serverURL: String
    public var email: String
    public var password: String = ""
    public var currentUser: SessionUser?
    public var portfolios: [PortfolioDescriptor] = []
    public var selectedPortfolioID: Int?
    public var authenticationState: AuthenticationState
    public var isRefreshing = false
    public var isBootstrapped = false
    public var errorMessage: String?
    public var addHoldingErrorMessage: String?

    private var authToken: String?
    private let userDefaults: UserDefaults

    private enum StorageKey {
        static let serverURL = "stockpile.serverURL"
        static let authToken = "stockpile.authToken"
        static let email = "stockpile.email"
        static let selectedPortfolioID = "stockpile.selectedPortfolioID"
    }

    public init(userDefaults: UserDefaults = .standard) {
        let storedServerURL = userDefaults.string(forKey: StorageKey.serverURL) ?? "https://stockpile.maxwood.me"
        let storedAuthToken = userDefaults.string(forKey: StorageKey.authToken)
        let storedEmail = userDefaults.string(forKey: StorageKey.email) ?? ""
        let storedPortfolioID = userDefaults.object(forKey: StorageKey.selectedPortfolioID) as? Int

        self.userDefaults = userDefaults
        self.serverURL = storedServerURL
        self.authToken = storedAuthToken
        self.email = storedEmail
        self.selectedPortfolioID = storedPortfolioID
        self.authenticationState = storedAuthToken == nil ? .signedOut : .signedIn
    }

    public var isAuthenticated: Bool {
        authenticationState == .signedIn && authToken?.isEmpty == false
    }

    public func bootstrap() async {
        guard !isBootstrapped else { return }
        isBootstrapped = true

        guard authToken != nil else {
            applyMockData()
            return
        }

        do {
            try await restoreSession()
            await refresh()
        } catch {
            signOutLocally()
            errorMessage = error.localizedDescription
        }
    }

    public func signIn() async {
        errorMessage = nil
        authenticationState = .signingIn

        do {
            let payload = try await apiClient().login(
                email: email,
                password: password,
                deviceName: deviceName
            )

            authToken = payload.token
            currentUser = payload.user
            portfolios = payload.portfolios
            selectedPortfolioID = payload.portfolios.first?.id
            password = ""
            authenticationState = .signedIn
            persistSession()
            await refresh()
        } catch {
            authenticationState = .signedOut
            errorMessage = error.localizedDescription
        }
    }

    public func signOut() async {
        do {
            try await apiClient().logout()
        } catch {
            // Clear local auth even if the server call fails.
        }

        signOutLocally()
    }

    public func refresh() async {
        guard isAuthenticated else {
            applyMockData()
            return
        }

        isRefreshing = true
        defer { isRefreshing = false }

        do {
            async let summaryRequest = apiClient().fetchSummary(portfolioID: selectedPortfolioID)
            async let holdingsRequest = apiClient().fetchHoldings(portfolioID: selectedPortfolioID)
            async let seriesRequest = apiClient().fetchPerformanceSeries(
                portfolioID: selectedPortfolioID,
                range: selectedPerformanceRange
            )
            async let journalRequest = apiClient().fetchJournal(portfolioID: selectedPortfolioID)

            summary = try await summaryRequest
            holdings = try await holdingsRequest.data
            performanceSeries = try await seriesRequest
            journalEntries = try await journalRequest
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription

            if case let APIError.http(status, _) = error, status == 401 {
                signOutLocally()
            }
        }
    }

    public func selectPortfolio(id: Int) {
        selectedPortfolioID = id
        userDefaults.set(id, forKey: StorageKey.selectedPortfolioID)
    }

    public func selectPerformanceRange(_ range: String) async {
        selectedPerformanceRange = range
        await refresh()
    }

    public func addHolding(
        symbol: String,
        tradeDate: String,
        purchasePrice: Double?,
        quantity: Double,
        totalCost: Double
    ) async -> Bool {
        guard isAuthenticated else {
            addHoldingErrorMessage = "Sign in before adding holdings."
            return false
        }

        guard symbol.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false else {
            addHoldingErrorMessage = "Ticker symbol is required."
            return false
        }

        guard quantity > 0, totalCost > 0 else {
            addHoldingErrorMessage = "Purchase date, shares, and total cost are required."
            return false
        }

        do {
            _ = try await apiClient().createHolding(
                portfolioID: selectedPortfolioID,
                symbol: symbol,
                tradeDate: tradeDate,
                purchasePrice: purchasePrice,
                quantity: quantity,
                totalCost: totalCost
            )
            addHoldingErrorMessage = nil
            await refresh()
            return true
        } catch {
            addHoldingErrorMessage = error.localizedDescription
            return false
        }
    }

    public func resetPortfolio() async -> Bool {
        guard isAuthenticated, let selectedPortfolioID else {
            errorMessage = "Sign in before resetting the portfolio."
            return false
        }

        do {
            let response = try await apiClient().resetPortfolio(id: selectedPortfolioID)
            let session = try await apiClient().fetchCurrentSession()
            currentUser = session.user
            portfolios = session.portfolios
            self.selectedPortfolioID = response.portfolio.id
            persistSession()
            await refresh()
            return true
        } catch {
            errorMessage = error.localizedDescription
            return false
        }
    }

    public func saveServerURL() {
        userDefaults.set(serverURL, forKey: StorageKey.serverURL)
    }

    private func restoreSession() async throws {
        let payload = try await apiClient().fetchCurrentSession()
        currentUser = payload.user
        portfolios = payload.portfolios

        if selectedPortfolioID == nil || portfolios.contains(where: { $0.id == selectedPortfolioID }) == false {
            selectedPortfolioID = portfolios.first?.id
        }

        persistSession()
        authenticationState = .signedIn
    }

    private func apiClient() -> PortfolioAPIClient {
        PortfolioAPIClient(
            baseURL: normalizedBaseURL(),
            tokenProvider: { [authToken] in authToken }
        )
    }

    private func normalizedBaseURL() -> URL {
        let trimmed = serverURL.trimmingCharacters(in: .whitespacesAndNewlines)
        let normalized = trimmed.hasSuffix("/") ? String(trimmed.dropLast()) : trimmed
        guard let url = URL(string: normalized + "/") else {
            return URL(string: "https://stockpile.maxwood.me/")!
        }

        return url
    }

    private func persistSession() {
        userDefaults.set(serverURL, forKey: StorageKey.serverURL)
        userDefaults.set(email, forKey: StorageKey.email)
        userDefaults.set(authToken, forKey: StorageKey.authToken)
        userDefaults.set(selectedPortfolioID, forKey: StorageKey.selectedPortfolioID)
    }

    private func signOutLocally() {
        authToken = nil
        currentUser = nil
        portfolios = []
        selectedPortfolioID = nil
        authenticationState = .signedOut
        password = ""
        userDefaults.removeObject(forKey: StorageKey.authToken)
        userDefaults.removeObject(forKey: StorageKey.selectedPortfolioID)
        applyMockData()
    }

    private func applyMockData() {
        summary = MockPortfolioData.summary
        holdings = MockPortfolioData.holdings.data
        performanceSeries = MockPortfolioData.series
        journalEntries = MockPortfolioData.journal
    }

    private var deviceName: String {
        #if os(macOS)
        return "stockpile-macos"
        #else
        return "stockpile-ios"
        #endif
    }
}
