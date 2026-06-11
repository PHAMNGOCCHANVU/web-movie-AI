import React, { createContext, useCallback, useContext, useMemo, useState } from 'react';
import { X } from 'lucide-react';

const UiContext = createContext(null);

export function UiProvider({ children }) {
    const [toasts, setToasts] = useState([]);
    const [modal, setModal] = useState(null);

    const toast = useCallback((message, type = 'success') => {
        const id = crypto.randomUUID();
        setToasts((items) => [...items, { id, message, type }]);
        window.setTimeout(() => {
            setToasts((items) => items.filter((item) => item.id !== id));
        }, 3500);
    }, []);

    const value = useMemo(() => ({
        toast,
        openModal: setModal,
        closeModal: () => setModal(null),
    }), [toast]);

    return (
        <UiContext.Provider value={value}>
            {children}

            <div className="fixed right-4 top-20 z-[80] flex w-[min(92vw,380px)] flex-col gap-3">
                {toasts.map((item) => (
                    <div
                        className={`toast ${item.type === 'error' ? 'toast--error' : ''}`}
                        key={item.id}
                    >
                        {item.message}
                    </div>
                ))}
            </div>

            {modal && (
                <div className="modal-backdrop" role="presentation" onMouseDown={() => setModal(null)}>
                    <div
                        className="modal-panel"
                        role="dialog"
                        aria-modal="true"
                        onMouseDown={(event) => event.stopPropagation()}
                    >
                        <button className="icon-button absolute right-4 top-4" onClick={() => setModal(null)}>
                            <X size={20} />
                        </button>
                        {modal}
                    </div>
                </div>
            )}
        </UiContext.Provider>
    );
}

export function useUi() {
    return useContext(UiContext);
}
