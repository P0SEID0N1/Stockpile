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
    }

    @ViewBuilder
    private var detailView: some View {
        switch model.selectedSection {
        case .dashboard:
            DashboardScreen(model: model)
        case .portfolio:
            HoldingsScreen(model: model)
        case .journal:
            JournalScreen(model: model)
        case .plan:
            PlanScreen(model: model)
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
        TabView(selection: $model.selectedSection) {
            NavigationStack { DashboardScreen(model: model) }
                .tabItem { Label("Dashboard", systemImage: "chart.line.uptrend.xyaxis") }
                .tag(PortfolioAppModel.Section.dashboard)

            NavigationStack { HoldingsScreen(model: model) }
                .tabItem { Label("Portfolio", systemImage: "briefcase") }
                .tag(PortfolioAppModel.Section.portfolio)

            NavigationStack { JournalScreen(model: model) }
                .tabItem { Label("Journal", systemImage: "book.closed") }
                .tag(PortfolioAppModel.Section.journal)

            NavigationStack { PlanScreen(model: model) }
                .tabItem { Label("Plan", systemImage: "scope") }
                .tag(PortfolioAppModel.Section.plan)

            NavigationStack { SettingsScreen(model: model) }
                .tabItem { Label("Settings", systemImage: "gearshape") }
                .tag(PortfolioAppModel.Section.settings)
        }
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
            Text("Benchmark: \(model.summary.benchmarkSymbol)")
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
            Text("Portfolio Growth")
                .font(.headline)
            Chart {
                ForEach(model.performanceSeries.portfolio) { point in
                    LineMark(x: .value("Date", point.date), y: .value("Portfolio", point.value))
                        .foregroundStyle(.blue)
                }
                ForEach(model.performanceSeries.benchmark) { point in
                    LineMark(x: .value("Date", point.date), y: .value("Benchmark", point.value))
                        .foregroundStyle(.green)
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
    }
}

struct JournalScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        List(model.journalEntries) { entry in
            VStack(alignment: .leading, spacing: 6) {
                HStack {
                    Text(entry.entryType.capitalized)
                        .font(.headline)
                    Spacer()
                    Text(entry.tradeDate)
                        .foregroundStyle(.secondary)
                }
                Text(entry.holding?.asset.symbol ?? entry.account?.name ?? "General note")
                    .font(.subheadline)
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

struct PlanScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        List(model.planner.targets) { target in
            VStack(alignment: .leading, spacing: 8) {
                HStack {
                    Text(target.label.capitalized)
                        .font(.headline)
                    Spacer()
                    Text("\(target.targetPercentage.formatted(.number.precision(.fractionLength(1))))% target")
                        .foregroundStyle(.secondary)
                }
                HStack {
                    Text("Current \(target.currentPercentage.formatted(.number.precision(.fractionLength(1))))%")
                    Spacer()
                    Text(currency(target.deltaValue))
                        .foregroundStyle(target.deltaValue >= 0 ? .green : .red)
                }
                .font(.footnote)
            }
        }
        .navigationTitle("Plan")
    }
}

struct SettingsScreen: View {
    let model: PortfolioAppModel

    var body: some View {
        Form {
            Section("Server") {
                Text(model.serverURL)
                Text("Point this app at your self-hosted Laravel instance.")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
            Section("Status") {
                if let errorMessage = model.errorMessage {
                    Text(errorMessage)
                        .foregroundStyle(.red)
                } else {
                    Text("Ready to sync")
                }
            }
        }
        .navigationTitle("Settings")
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
