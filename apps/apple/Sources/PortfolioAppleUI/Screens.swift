import Charts
import SwiftUI
import PortfolioCore

#if os(macOS)
public struct PortfolioDesktopRootView: View {
    @Bindable private var model: PortfolioAppModel

    public init(model: PortfolioAppModel) {
        self.model = model
    }

    public var body: some View {
        Group {
            if model.isAuthenticated {
                NavigationSplitView {
                    List(PortfolioAppModel.Section.allCases, selection: $model.selectedSection) { section in
                        Label(section.title, systemImage: section.symbolName)
                            .tag(section)
                    }
                    .navigationTitle("Stockpile")
                    .listStyle(.sidebar)
                } detail: {
                    detailView
                        .toolbar {
                            ToolbarItem(placement: .primaryAction) {
                                Button {
                                    Task { await model.refresh() }
                                } label: {
                                    Label("Refresh", systemImage: "arrow.clockwise")
                                }
                            }
                        }
                }
            } else {
                AuthenticationScreen(model: model)
                    .frame(minWidth: 520, minHeight: 560)
            }
        }
        .task {
            await model.bootstrap()
        }
    }

    @ViewBuilder
    private var detailView: some View {
        switch model.selectedSection {
        case .dashboard:
            DashboardScreen(model: model)
        case .portfolio:
            HoldingsScreen(model: model)
        case .performance:
            PerformanceScreen(model: model)
        case .journal:
            JournalScreen(model: model)
        case .settings:
            SettingsScreen(model: model)
        }
    }
}
#endif

public struct PortfolioMobileRootView: View {
    @Bindable private var model: PortfolioAppModel

    public init(model: PortfolioAppModel) {
        self.model = model
    }

    public var body: some View {
        Group {
            if model.isAuthenticated {
                TabView(selection: $model.selectedSection) {
                    NavigationStack { DashboardScreen(model: model) }
                        .tabItem { Label("Dashboard", systemImage: "chart.line.uptrend.xyaxis") }
                        .tag(PortfolioAppModel.Section.dashboard)

                    NavigationStack { HoldingsScreen(model: model) }
                        .tabItem { Label("Portfolio", systemImage: "briefcase") }
                        .tag(PortfolioAppModel.Section.portfolio)

                    NavigationStack { PerformanceScreen(model: model) }
                        .tabItem { Label("Performance", systemImage: "globe.americas") }
                        .tag(PortfolioAppModel.Section.performance)

                    NavigationStack { JournalScreen(model: model) }
                        .tabItem { Label("Journal", systemImage: "book.closed") }
                        .tag(PortfolioAppModel.Section.journal)

                    NavigationStack { SettingsScreen(model: model) }
                        .tabItem { Label("Settings", systemImage: "gearshape") }
                        .tag(PortfolioAppModel.Section.settings)
                }
            } else {
                NavigationStack {
                    AuthenticationScreen(model: model)
                }
            }
        }
        .task {
            await model.bootstrap()
        }
    }
}

struct AuthenticationScreen: View {
    @Bindable var model: PortfolioAppModel

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 24) {
                VStack(alignment: .leading, spacing: 8) {
                    Text("Stockpile")
                        .font(.largeTitle.bold())
                    Text("Sign in to your self-hosted portfolio server.")
                        .foregroundStyle(.secondary)
                }

                VStack(alignment: .leading, spacing: 16) {
                    TextField("Server URL", text: $model.serverURL)
                        .textFieldStyle(.roundedBorder)
                        #if !os(macOS)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .keyboardType(.URL)
                        #endif

                    TextField("Email", text: $model.email)
                        .textFieldStyle(.roundedBorder)
                        #if !os(macOS)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .keyboardType(.emailAddress)
                        #endif

                    SecureField("Password", text: $model.password)
                        .textFieldStyle(.roundedBorder)

                    if let errorMessage = model.errorMessage {
                        Text(errorMessage)
                            .font(.footnote)
                            .foregroundStyle(.red)
                    }

                    Button {
                        model.saveServerURL()
                        Task { await model.signIn() }
                    } label: {
                        if model.authenticationState == .signingIn {
                            ProgressView()
                                .frame(maxWidth: .infinity)
                        } else {
                            Text("Sign In")
                                .frame(maxWidth: .infinity)
                        }
                    }
                    .buttonStyle(.borderedProminent)
                    .disabled(model.authenticationState == .signingIn || model.serverURL.isEmpty || model.email.isEmpty || model.password.isEmpty)
                }

                VStack(alignment: .leading, spacing: 8) {
                    Text("Requirements")
                        .font(.headline)
                    Text("Use your HTTPS production URL, for example `https://stockpile.maxwood.me`.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    Text("The first account must already exist on the server via `/setup`.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
            .padding(24)
            .frame(maxWidth: 520, alignment: .leading)
        }
        .navigationTitle("Login")
    }
}

struct DashboardScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 20) {
                header
                metricGrid
                chartCard
                allocationCard
            }
            .padding()
        }
        .navigationTitle("Dashboard")
    }

    private var header: some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(model.summary.portfolioName)
                .font(.largeTitle.bold())
            Text("Benchmark: \(model.summary.benchmarkLabel ?? model.summary.benchmarkSymbol)")
                .foregroundStyle(.secondary)
        }
    }

    private var metricGrid: some View {
        LazyVGrid(columns: [GridItem(.adaptive(minimum: 180), spacing: 16)], spacing: 16) {
            MetricCard(title: "Current Value", value: currency(model.summary.currentValue), tint: .blue)
            MetricCard(title: "Gain / Loss", value: currency(model.summary.totalGainLoss), tint: model.summary.totalGainLoss >= 0 ? .green : .red)
            MetricCard(title: "Day Change", value: "\(currency(model.summary.dayChange)) (\(model.summary.dayChangePercent.formatted(.number.precision(.fractionLength(2))))%)", tint: model.summary.dayChange >= 0 ? .green : .red)
            MetricCard(title: "Positions", value: "\(model.summary.holdingsCount)", tint: .orange)
        }
    }

    private var chartCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Text("Portfolio Value")
                    .font(.headline)
                Spacer()
                RangePicker(model: model)
            }
            Chart {
                ForEach(model.performanceSeries.portfolio) { point in
                    LineMark(x: .value("Date", point.date), y: .value("Portfolio", point.value))
                        .foregroundStyle(.blue)
                }
            }
            .frame(height: 240)
        }
        .cardStyle()
    }

    private var allocationCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Asset Allocation")
                .font(.headline)
            ForEach(model.summary.assetTypeAllocation) { slice in
                VStack(alignment: .leading, spacing: 6) {
                    HStack {
                        Text(slice.label.capitalized)
                        Spacer()
                        Text("\(slice.percentage.formatted(.number.precision(.fractionLength(1))))%")
                            .foregroundStyle(.secondary)
                    }
                    ProgressView(value: slice.percentage, total: 100)
                }
            }
        }
        .cardStyle()
    }
}

struct HoldingsScreen: View {
    @State private var isPresentingAddHolding = false
    let model: PortfolioAppModel

    var body: some View {
        List(model.holdings) { holding in
            VStack(alignment: .leading, spacing: 8) {
                HStack {
                    VStack(alignment: .leading, spacing: 2) {
                        Text(holding.asset.symbol ?? holding.asset.name)
                            .font(.headline)
                        Text(holding.asset.name)
                            .font(.subheadline)
                            .foregroundStyle(.secondary)
                    }
                    Spacer()
                    Text(currency(Double(holding.marketValue ?? holding.costBasisTotal) ?? 0))
                        .font(.headline)
                }
                HStack {
                    Label(holding.account.name, systemImage: "briefcase")
                    Spacer()
                    Text("Qty \(holding.quantity)")
                    Text("Basis \(currency(Double(holding.costBasisTotal) ?? 0))")
                }
                .font(.footnote)
                .foregroundStyle(.secondary)
            }
            .padding(.vertical, 4)
        }
        .navigationTitle("Portfolio")
        .toolbar {
            ToolbarItem(placement: .primaryAction) {
                Button {
                    isPresentingAddHolding = true
                } label: {
                    Label("Add Holding", systemImage: "plus")
                }
            }
        }
        .sheet(isPresented: $isPresentingAddHolding) {
            AddHoldingScreen(model: model)
        }
    }
}

struct AddHoldingScreen: View {
    @Environment(\.dismiss) private var dismiss
    @Bindable var model: PortfolioAppModel
    @State private var symbol = ""
    @State private var tradeDate = Date.now
    @State private var purchasePrice = ""
    @State private var quantity = ""
    @State private var totalCost = ""
    @State private var isSaving = false

    var body: some View {
        NavigationStack {
            Form {
                Section("Holding") {
                    TextField("Ticker symbol", text: $symbol)
                        #if !os(macOS)
                        .textInputAutocapitalization(.characters)
                        .autocorrectionDisabled()
                        #endif
                    DatePicker("Purchase date", selection: $tradeDate, displayedComponents: .date)
                    TextField("Price paid per share (optional)", text: $purchasePrice)
                        #if !os(macOS)
                        .keyboardType(.decimalPad)
                        #endif
                    TextField("Number of shares", text: $quantity)
                        #if !os(macOS)
                        .keyboardType(.decimalPad)
                        #endif
                    TextField("Total cost", text: $totalCost)
                        #if !os(macOS)
                        .keyboardType(.decimalPad)
                        #endif
                    Text("If total cost and share count are filled in, the app derives the per-share price automatically.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }

                Section("Quote source") {
                    Text("The portfolio history is rebuilt from dated transactions plus the backend market-data provider, including automatic DRIP where dividend history is available.")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }

                if let errorMessage = model.addHoldingErrorMessage {
                    Section {
                        Text(errorMessage)
                            .font(.footnote)
                            .foregroundStyle(.red)
                    }
                }
            }
            .navigationTitle("Add Holding")
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") {
                        dismiss()
                    }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Save") {
                        Task {
                            await save()
                        }
                    }
                    .disabled(isSaving || symbol.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || quantity.isEmpty || totalCost.isEmpty)
                }
            }
        }
    }

    private func save() async {
        guard let parsedQuantity = Double(quantity),
              let parsedTotalCost = Double(totalCost) else {
            model.addHoldingErrorMessage = "Enter valid numeric values for shares and total cost."
            return
        }

        let trimmedPurchasePrice = purchasePrice.trimmingCharacters(in: .whitespacesAndNewlines)
        let parsedPurchasePrice = trimmedPurchasePrice.isEmpty ? nil : Double(trimmedPurchasePrice)

        if !trimmedPurchasePrice.isEmpty && parsedPurchasePrice == nil {
            model.addHoldingErrorMessage = "Enter a valid numeric value for price paid per share."
            return
        }

        isSaving = true
        defer { isSaving = false }

        let success = await model.addHolding(
            symbol: symbol,
            tradeDate: isoDate(tradeDate),
            purchasePrice: parsedPurchasePrice,
            quantity: parsedQuantity,
            totalCost: parsedTotalCost
        )

        if success {
            dismiss()
        }
    }
}

struct JournalScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        List(model.journalEntries) { entry in
            VStack(alignment: .leading, spacing: 6) {
                HStack {
                    Text(entry.entryType.replacingOccurrences(of: "_", with: " ").capitalized)
                        .font(.headline)
                    Spacer()
                    Text(entry.tradeDate)
                        .foregroundStyle(.secondary)
                }
                Text(entry.asset?.symbol ?? entry.holding?.asset.symbol ?? entry.account?.name ?? "General note")
                    .font(.subheadline)
                HStack {
                    if let quantity = entry.quantity {
                        Text("Qty \(quantity)")
                    }
                    if let price = entry.pricePerUnit {
                        Text("Price \(currency(Double(price) ?? 0))")
                    }
                    if let amount = entry.amount {
                        Text("Cash \(currency(Double(amount) ?? 0))")
                    }
                }
                .font(.footnote)
                .foregroundStyle(.secondary)
                Text(entry.sourceType.replacingOccurrences(of: "_", with: " ").capitalized)
                    .font(.caption)
                    .foregroundStyle(.tertiary)
                if let notes = entry.notes {
                    Text(notes)
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                }
            }
        }
        .navigationTitle("Journal")
    }
}

struct PerformanceScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        ScrollView {
            VStack(alignment: .leading, spacing: 16) {
                HStack {
                    Text("Performance vs US Market")
                        .font(.title2.bold())
                    Spacer()
                    RangePicker(model: model)
                }
                Chart {
                    ForEach(model.performanceSeries.comparisonPortfolio) { point in
                        LineMark(x: .value("Date", point.date), y: .value("Portfolio", point.value))
                            .foregroundStyle(.blue)
                    }
                    ForEach(model.performanceSeries.benchmark) { point in
                        LineMark(x: .value("Date", point.date), y: .value("Benchmark", point.value))
                            .foregroundStyle(.green)
                    }
                }
                .frame(height: 260)
                summaryCard
            }
            .padding()
        }
        .navigationTitle("Performance")
    }

    private var summaryCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(model.performanceSeries.benchmarkLabel)
                .font(.headline)
            HStack {
                MetricCard(title: "Portfolio Return", value: "\(model.performanceSeries.portfolioReturnPercent.formatted(.number.precision(.fractionLength(2))))%", tint: model.performanceSeries.portfolioReturnPercent >= 0 ? .green : .red)
                MetricCard(title: "Benchmark Return", value: "\(model.performanceSeries.benchmarkReturnPercent.formatted(.number.precision(.fractionLength(2))))%", tint: model.performanceSeries.benchmarkReturnPercent >= 0 ? .green : .red)
            }
            MetricCard(title: "Outperformance", value: "\((model.performanceSeries.portfolioReturnPercent - model.performanceSeries.benchmarkReturnPercent).formatted(.number.precision(.fractionLength(2))))%", tint: (model.performanceSeries.portfolioReturnPercent - model.performanceSeries.benchmarkReturnPercent) >= 0 ? .green : .red)
        }
        .cardStyle()
    }
}

struct SettingsScreen: View {
    @Bindable var model: PortfolioAppModel
    @State private var isConfirmingReset = false

    var body: some View {
        Form {
            Section("Server") {
                TextField("Server URL", text: $model.serverURL)
                    #if !os(macOS)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                    .keyboardType(.URL)
                    #endif
                Button("Save Server URL") {
                    model.saveServerURL()
                }
                Text("Point this app at your self-hosted Laravel instance root URL.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Section("Account") {
                Text(model.currentUser?.email ?? model.email)
                if model.portfolios.isEmpty == false {
                    Picker("Portfolio", selection: Binding(
                        get: { model.selectedPortfolioID ?? model.portfolios.first?.id ?? 0 },
                        set: { newValue in
                            model.selectPortfolio(id: newValue)
                            Task { await model.refresh() }
                        }
                    )) {
                        ForEach(model.portfolios) { portfolio in
                            Text(portfolio.name).tag(portfolio.id)
                        }
                    }
                }

                Button("Sign Out", role: .destructive) {
                    Task { await model.signOut() }
                }
            }
            Section("Danger Zone") {
                Button("Wipe Portfolio and Start Over", role: .destructive) {
                    isConfirmingReset = true
                }
                Text("Deletes holdings, transactions, snapshots, and imports for the selected portfolio, then recreates a fresh empty replacement.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Section("Status") {
                if let errorMessage = model.errorMessage {
                    Text(errorMessage)
                        .foregroundStyle(.red)
                } else {
                    Text(model.isRefreshing ? "Refreshing..." : "Ready to sync")
                }
            }
        }
        .navigationTitle("Settings")
        .alert("Wipe Portfolio?", isPresented: $isConfirmingReset) {
            Button("Cancel", role: .cancel) { }
            Button("Wipe", role: .destructive) {
                Task { _ = await model.resetPortfolio() }
            }
        } message: {
            Text("This permanently deletes the current portfolio data and creates a fresh empty replacement.")
        }
    }
}

struct RangePicker: View {
    @Bindable var model: PortfolioAppModel

    private let ranges = ["1d", "5d", "1m", "3m", "1y", "ytd"]

    var body: some View {
        HStack(spacing: 6) {
            ForEach(ranges, id: \.self) { range in
                Button(range.uppercased()) {
                    Task { await model.selectPerformanceRange(range) }
                }
                .buttonStyle(.borderedProminent)
                .tint(model.selectedPerformanceRange == range ? .accentColor : .gray.opacity(0.35))
                .controlSize(.small)
            }
        }
    }
}

struct MetricCard: View {
    let title: String
    let value: String
    let tint: Color

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.subheadline)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.title3.weight(.semibold))
                .foregroundStyle(tint)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
    }
}

private extension View {
    func cardStyle() -> some View {
        padding()
            .background(.regularMaterial, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
    }
}

private func currency(_ value: Double) -> String {
    value.formatted(.currency(code: "USD"))
}

private func isoDate(_ date: Date) -> String {
    let formatter = DateFormatter()
    formatter.calendar = Calendar(identifier: .gregorian)
    formatter.locale = Locale(identifier: "en_US_POSIX")
    formatter.dateFormat = "yyyy-MM-dd"
    return formatter.string(from: date)
}
